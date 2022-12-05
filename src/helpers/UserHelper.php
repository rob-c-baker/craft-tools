<?php
declare(strict_types=1);

namespace alanrogers\tools\helpers;

use alanrogers\tools\models\Email;
use alanrogers\tools\queue\jobs\SendCustomEmail;
use Craft;
use craft\elements\User;
use InvalidArgumentException;

class UserHelper implements HelperInterface
{
    /**
     * Sends (adds jobs to queue) an email to each member of the specified group
     * @param Email $email
     * @param string $user_group User Group handle
     */
    public static function sendEmailToUserGroup(Email $email, string $user_group) : void
    {
        $group = Craft::$app->getUserGroups()->getGroupByHandle($user_group);
        if (!$group) {
            throw new InvalidArgumentException(sprintf('Unknown user group: %s', $user_group));
        }

        $users = User::find()->group($group)->all();
        $queue = Craft::$app->getQueue();

        foreach ($users as $user) {
            $job = new SendCustomEmail([
                'from' => $email->from,
                'to' => (string) $user->email,
                'reply_to' => $email->reply_to,
                'subject' => $email->subject,
                'text_body' => $email->text,
                'html_body' => $email->html
            ]);
            $queue->delay(0)->push($job);
        }
    }

    /**
     * Sends (adds jobs to queue) an email to each user that is an admin
     * @param Email $email
     */
    public static function setEmailToAdmins(Email $email) : void
    {
        $users = User::find()->admin()->all();
        $queue = Craft::$app->getQueue();

        foreach ($users as $user) {
            $job = new SendCustomEmail([
                'from' => $email->from,
                'to' => (string) $user->email,
                'reply_to' => $email->reply_to,
                'subject' => $email->subject,
                'text_body' => $email->text,
                'html_body' => $email->html
            ]);
            $queue->delay(0)->push($job);
        }
    }
}