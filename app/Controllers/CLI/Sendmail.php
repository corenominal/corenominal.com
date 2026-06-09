<?php

namespace App\Controllers\CLI;

use App\Controllers\BaseController;
use CodeIgniter\CLI\CLI;

/**
 * @internal
 */
final class Sendmail extends BaseController
{
    /**
     * Processes unsent email messages and sends them using the configured email service.
     *
     * This method retrieves messages from the database that are not sent, not deleted,
     * and are older than 5 minutes. It processes up to 30 messages at a time.
     * For each message, it attempts to send an email and updates the message status
     * in the database based on the result.
     *
     * Workflow:
     * - Fetch unsent messages from the database.
     * - Log the number of messages found or indicate if there are none.
     * - Loop through each message:
     *   - Configure the email service with SMTP credentials.
     *   - Set email details (from, to, subject, body, CC, BCC).
     *   - Attempt to send the email.
     *   - Log success or failure and update the message status in the database.
     *
     * Error Handling:
     * - If an email fails to send, the error is logged, and the message is marked as failed.
     * - If an email is sent successfully, the message is marked as sent.
     *
     * Usage:
     * - This method is intended to be run from the command line interface (CLI).
     * - It is typically scheduled to run periodically using a cron job or similar task scheduler.
     * 
     * Usage example:
     * sudo -u _www php /path/to/codeigniter/public/index.php cli/sendmail process
     * or
     * php public/index.php cli/sendmail process
     * @return void
     */
    public function process()
    {
        // Get number of messages sent in the last 60 minutes
        $model = model('MessageModel');
        $model->where('sent_at >=', date('Y-m-d H:i:s', strtotime('-60 minutes')));
        $model->where('sent_at <=', date('Y-m-d H:i:s'));
        $model->where('deleted_at', null);
        $sent_last_60_count = $model->countAllResults();
        // If more than 80 messages are sent in the last 60 minutes, exit
        if ($sent_last_60_count > 80) {
            CLI::write('Too many messages sent in the last 60 minutes: ' . $sent_last_60_count, 'red');
            return;
        }
        CLI::write('Sent messages in the last 60 minutes: ' . $sent_last_60_count, 'green');
        // Get the message model
        $model = model('MessageModel');
        $messages = $model->where('sent_at', null)
                ->where('deleted_at', null)
                ->limit(10)
                ->findAll();
        
        // Check if there are any messages to send
        if (empty($messages)) {
            CLI::write('No messages to send', 'yellow');
            // Log the message and exit
            logit('No messages to send', 0);
            return;
        } else {
            CLI::write('Found ' . count($messages) . ' messages to send ...');
        }

        // Set count for number of messages sent in this batch
        $sent_count = 0;

        // Loop through the messages
        foreach ($messages as $message) {
            // Send email notification
            $email = service('email');
            $email->setFrom($message['from']);
            $email->setTo($message['to']);
            $email->setSubject($message['subject']);
            $email->setMailType($message['mailtype']);
            $email->setMessage($message['body']);
            // Check if there are any CC or BCC recipients
            if (!empty($message['cc'])) {
                $email->setCC($message['cc']);
            }
            if (!empty($message['bcc'])) {
                $email->setBCC($message['bcc']);
            }
            // Send the email
            // Check if the email was sent successfully
            if (!$email->send(false)) {
                // If the email was not sent, log the error
                CLI::write('Error sending message ' . $message['id'], 'red');
                // Update the message as failed
                $model->update($message['id'], [
                    'sent_at' => null,
                    'deleted_at' => date('Y-m-d H:i:s'),
                ]);
                CLI::write('Message ' . $message['id'] . ' marked as failed', 'red');
                // Log the error message
                logit('Error sending message ' . $message['id'], 2);
                continue;
            }
            // If the email was sent successfully, update the message as sent
            $model->update($message['id'], [
                'sent_at' => date('Y-m-d H:i:s'),
                'deleted_at' => null,
            ]);
            // Increment the sent count
            $sent_count++;
            // Log the success
            CLI::write('Message ' . $message['id'] . ' sent successfully', 'green');
        }
        CLI::write('Total messages sent in this batch: ' . $sent_count, 'green');
        // Log the total messages sent in this batch
        logit('Total messages sent in this batch: ' . $sent_count, 0);
    }
}
