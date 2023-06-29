<?php

namespace alanrogers\tools\queue;
use craft\helpers\Db;
use craft\queue\JobInterface;
use craft\queue\Queue;
use Exception;
use yii\base\Application as YiiApplication;
use yii\base\Event;
use yii\base\Application;

class ARQueue extends Queue
{
    /**
     * @var ARQueueMessage[]
     */
    protected array $messages = [];
    protected bool $event_added = false;
    private ?string $_jobDesc;

    public function push($job): ?string
    {
        // Capture the description so pushMessage() can access it
        if ($job instanceof JobInterface) {
            $this->_jobDesc = $job->getDescription();
        } else {
            $this->_jobDesc = null;
        }

        return parent::push($job);
    }

    protected function pushMessage($message, $ttr, $delay, $priority): string
    {
        $id = self::makeId();
        $this->messages[$id] = new ARQueueMessage($id, $message, $this->_jobDesc, $ttr, $delay, $priority);
        if (!$this->event_added) {
            $this->addEvent();
            $this->event_added = true;
        }
        return $id;
    }

    protected function addEvent()
    {
        Event::on(
            Application::class,
            YiiApplication::EVENT_AFTER_REQUEST,
            function (Event $event) {
                $this->flushMessages();
            }
        );
    }

    protected static function makeId() : string
    {
        // Essentially, generate a v4 UUID
        try {
            $data = random_bytes(16);
        } catch (Exception) {
            $data = mt_rand(0, 15);
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function flushMessages()
    {
        $messages = [];

        $columns = [
            'channel',
            'job',
            'description',
            'timePushed',
            'ttr',
            'delay',
            'priority'
        ];

        foreach ($this->messages as $m) {
            $messages[] = [
                'channel' => $this->channel ?: 'queue',
                'job' => $m->message,
                'description' => $m->description,
                'timePushed' => $m->timePushed,
                'ttr' => $m->ttr,
                'delay' => $m->delay,
                'priority' => $m->priority ?: 1024,
            ];
        }

        try {
            $insert_count = Db::batchInsert($this->tableName, $columns, $messages, $this->db);
        } catch (\yii\db\Exception $e) {
            // @todo
        }

        // @Todo join the ids back up?
    }
}