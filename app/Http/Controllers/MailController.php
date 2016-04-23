<?php

namespace App\Http\Controllers;

// controllers
// model
use App\Attachment;
use App\Emails;
use App\Thread;
// classes
use Crypt;
use ForceUTF8\Encoding;
use PhpImap\Mailbox as ImapMailbox;

/**
 * ======================================
 * MailController.
 * ======================================
 * This Controller is used to fetch all the mails of any email.
 *
 * @author Ladybird <info@ladybirdweb.com>
 */
class MailController extends Controller
{
  public function readmails()
  {
    // get all the emails
    $emails = Emails::get();
    // fetch each mails by mails
    foreach ($emails as $email) {
      $email_id = $email->email_address;
      $password = Crypt::decrypt($email->password);
      $protocol = '/' . $email->fetching_protocol;
      $host = $email->fetching_host;
      $port = $email->fetching_port;
      $encryption = '/' . $email->fetching_encryption;
      $mailbox = new ImapMailbox('{' . $host . ':' . $port . $protocol . $encryption . '}INBOX', $email_id, $password, __DIR__);
      // fetch mail by one day previous
      $mailsIds = $mailbox->searchMailBox('SINCE ' . date('d-M-Y', strtotime('-30 day')));
      if (!$mailsIds) {
        die('Mailbox is empty');
      }

      /*
       *  put the newest emails on top
       *  rsort($mailsIds);
      **/

      foreach ($mailsIds as $mailId) {
        // get overview of mails
        /*
         *  2 bugs in files #19
         *  https://github.com/ladybirdweb/faveo-helpdesk/issues/19
         **/
        $overview = $overview = $mailbox->getMailsInfo(array($mailId));

        //dd($overview);
        /*
         *  One single email from imap gives me:
         *  +"subject": "Sign-in attempt prevented"
         *  +"from": "Google <no-reply@accounts.google.com>"
         *  +"to": "kantoordigitaal@gmail.com"
         *  +"date": "Sun, 3 Apr 2016 09:14:22 +0000 (UTC)"
         *  +"message_id": "<sFDO66465EiJaqU9-rColQ@notifications.google.com>"
         *  +"size": 29813
         *  +"uid": 125
         *  +"msgno": 77
         *  +"recent": 0
         *  +"flagged": 0
         *  +"answered": 0
         *  +"deleted": 0
         *  +"seen": 0
         *  +"draft": 0
         *  +"udate": 1459674868
         **/

        /*
         *  $headers = imap_headers($mail);
         *  This returns an array in which each element is a formatted string corresponding to a message:
         **/
        /* get information specific to this email */
        //$overview = imap_fetch_overview($inbox,$email_number,0);
        // check if mails are unread
        $var = $overview[0]->seen ? 'read' : 'unread';
        // load only unread mails
        if ($var == 'read') {
          /*
           *  Email already read, still retrieve?
          */
        } else {
          $mail = $mailbox->getMail($mailId);
          $body = $mail->textHtml;
          // if mail body has no messages fetch backup mail
          /*
          if ($body == null) {
            $body = $mailbox->backup_getmail($mailId);
            $body = str_replace('\r\n', '<br/>', $body);
          }
          */
          // check if mail has subject
          if (isset($mail->subject)) {
            $subject = $mail->subject;
          } else {
            $subject = 'No Subject';
          }
          // fetch mail from details
          $fromname = $mail->fromName;
          $fromaddress = $mail->fromAddress;
          // store mail details id thread tables
          $thread = new Thread();
          $thread->name = $fromname;
          $thread->email = $fromaddress;
          $thread->title = $subject;
          $thread->body = $body;
          $thread->save();
          // fetch mail attachments
          foreach ($mail->getAttachments() as $attachment) {
            $support = 'support';
            $dir_img_paths = __DIR__;
            $dir_img_path = explode('/code', $dir_img_paths);

            $filepath = $_ENV["FILES_DIR"];

            $filepath = explode('..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'public', $attachment->filePath);
            $path = public_path() . $filepath[1];

            $filesize = filesize($path);
            $file_data = file_get_contents($path);
            $ext = pathinfo($attachment->filePath, PATHINFO_EXTENSION);
            $imageid = $attachment->id;
            $string = str_replace('-', '', $attachment->name);
            $filename = explode('src', $attachment->filePath);
            $filename = str_replace('\\', '', $filename);
            $body = str_replace('cid:' . $imageid, $filepath[1], $body);
            $pos = strpos($body, $filepath[1]);

            if ($pos == false) {
              $upload = new Attachment();
              $upload->file = $file_data;
              $upload->thread_id = $thread->id;
              $upload->name = $filepath[1];
              $upload->type = $ext;
              $upload->size = $filesize;
              $upload->poster = 'ATTACHMENT';
              $upload->save();
            } else {
              $upload = new Attachment();
              $upload->file = $file_data;
              $upload->thread_id = $thread->id;
              $upload->name = $filepath[1];
              $upload->type = $ext;
              $upload->size = $filesize;
              $upload->poster = 'INLINE';
              $upload->save();
            }
            unlink($path);
          }
          // run an encoding fix before saving mail details
          $body = Encoding::fixUTF8($body);
          /*
           *  Let's do some HTML purifying here
           **/

          $thread->body = $body;
          $thread->save();
        }
      }
    }

    return redirect()->route('home');
  }

  /**
   * fetch_attachments.
   *
   * @return type
   */
  public function fetch_attachments()
  {
    $uploads = Upload::all();
    foreach ($uploads as $attachment) {
      $image = @imagecreatefromstring($attachment->file);
      ob_start();
      imagejpeg($image, null, 80);
      $data = ob_get_contents();
      ob_end_clean();
      $var = '<a href="" target="_blank"><img src="data:image/jpg;base64,' . base64_encode($data) . '"/></a>';
      echo '<br/><span class="mailbox-attachment-icon has-img">' . $var . '</span>';
    }
  }

  /**
   * function to load data.
   *
   * @param type $id
   *
   * @return type file
   */
  public function get_data($id)
  {
    $attachments = Attachment::where('id', '=', $id)->get();
    foreach ($attachments as $attachment) {
      header('Content-type: application/' . $attachment->type . '');
      header('Content-Disposition: inline; filename=' . $attachment->name . '');
      header('Content-Transfer-Encoding: binary');
      echo $attachment->file;
    }
  }
}
