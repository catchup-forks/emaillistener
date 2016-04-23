<?php

use App\Model\helpdesk\Agent\Groups;
use App\Model\helpdesk\Agent\Teams;
use App\Model\helpdesk\Email\Smtp;



//use App\Model\helpdesk\Settings\Company;
use App\Model\helpdesk\Settings\Email;





use App\Model\MailboxProtocol;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* Mailbox protocol */
        $mailbox = [
            'IMAP'                 => '/imap',
            'IMAP+SSL'             => '/imap/ssl',
            'IMAP+TLS'             => '/imap/tls',
            'IMAP+SSL/No-validate' => '/imap/ssl/novalidate-cert', ];

        foreach ($mailbox as $name => $value) {
            MailboxProtocol::create(['name' => $name, 'value' => $value]);
        }


        /* Languages */
        $languages = [
            'English'              => 'en',
            'Dutch'                => 'nl',
        ];


        /* Teams */
        Teams::create(['name' => 'Level 1 Support', 'status' => '1']);
        Teams::create(['name' => 'Level 2 Support']);
        Teams::create(['name' => 'Developer']);
        /* Groups */
        Groups::create(['name' => 'Group A', 'group_status' => '1', 'can_create_ticket' => '1', 'can_edit_ticket' => '1', 'can_post_ticket' => '1', 'can_close_ticket' => '1', 'can_assign_ticket' => '1', 'can_transfer_ticket' => '1', 'can_delete_ticket' => '1', 'can_ban_email' => '1', 'can_manage_canned' => '1', 'can_view_agent_stats' => '1', 'department_access' => '1']);
        Groups::create(['name' => 'Group B', 'group_status' => '1', 'can_create_ticket' => '1', 'can_edit_ticket' => '0', 'can_post_ticket' => '0', 'can_close_ticket' => '1', 'can_assign_ticket' => '1', 'can_transfer_ticket' => '1', 'can_delete_ticket' => '1', 'can_ban_email' => '1', 'can_manage_canned' => '1', 'can_view_agent_stats' => '1', 'department_access' => '1']);
        Groups::create(['name' => 'Group C', 'group_status' => '1', 'can_create_ticket' => '0', 'can_edit_ticket' => '0', 'can_post_ticket' => '0', 'can_close_ticket' => '1', 'can_assign_ticket' => '0', 'can_transfer_ticket' => '0', 'can_delete_ticket' => '0', 'can_ban_email' => '0', 'can_manage_canned' => '0', 'can_view_agent_stats' => '0', 'department_access' => '0']);

        //Company::create(['id' => '1']);

        Email::create(['id' => '1', 'template' => 'default', 'email_fetching' => '1', 'notification_cron' => '1', 'all_emails' => '1', 'email_collaborator' => '1', 'attachment' => '1']);


        /* Mail configuration */
        Smtp::create(['id' => '1']);
        /* Version check */

        /* Knowledge base setting */
        //Settings::create(['id' => 'id', 'pagination' => '10']);
    }
}
