<?php

namespace PhpSimsimiSlackBot;

class SimsimiCommand extends \PhpSlackBot\Command\BaseCommand
{
    const API_URL  = 'http://www.simsimi.com/';

    protected $lc = null;
    protected $ft = null;
    protected $uuid = array();

    public function __construct($lc = 'en', $ft = 1)
    {
        $this->lc = $lc;
        $this->ft = $ft;
    }

    protected function configure()
    {
    }

    protected function execute($data, $context)
    {
        if (!isset($data['type']) || !isset($data['user']) || !isset($data['text'])) {
            return;
        }

        if ($data['type'] == 'message') {
            if ($data['user'] == $context['self']['id']) {
                return;
            }

            $mention_self = '<@' . $context['self']['id'] . '>';
            $mention_self_text_position = strpos($data['text'], $mention_self);
            $channel = $this->getChannelNameFromChannelId($data['channel']);

            if ($mention_self_text_position === false && $channel) {
                return;
            }

            if (!isset($data['thread_ts'])) {
                $data['thread_ts'] = null;
            }

            $text = str_replace($mention_self, '', $data['text']);
            $text = preg_replace('/(^|\s)[\p{C}]*($|\s)/', ' ', $text);
            $text = trim($text);

            // TODO : Set own UUID homie
            if (strtolower($text) == 'ping') {
                $message = str_replace(array('i', 'I'), array('o', 'O'), $text);
            } elseif (strtolower($text) == 'initialize') {
                $this->uuid[$data['channel']] = $this->getUUID();
                $message = 'UUID:' . $this->uuid[$data['channel']];
            } else {
                if (!isset($this->uuid[$data['channel']])) {
                    $message = 'No UUID found! Message me "initialize".';
                } elseif (!$this->checkUUID($this->uuid[$data['channel']])) {
                    $message = 'UUID error! Message me "initialize".';
                } else {
                    $options = array(
                        'lc' => $this->lc,
                        'ft' => $this->ft,
                        'uuid' => $this->uuid[$data['channel']],
                        'status' => 'W',
                        'reqText' => $text,
                    );

                    $response = $this->request(self::API_URL . 'getRealtimeReq', $options);
                    if (!is_object($response)) {
                        $message = $response;
                    } elseif (isset($response->errno)) {
                        $message = $response->errno . ':' . $response->code;
                    } else {
                        $message = $response->respSentence;
                    }
                }
            }

            // TODO : Send PR for threaded reply support
            $this->send($data['channel'], $data['user'], $message, $data['thread_ts']);
        }
    }

    protected function getUUID()
    {
        $response = $this->request(self::API_URL . 'getUUID');
        return $response->uuid;
    }

    protected function checkUUID($uuid)
    {
        $options = array(
            'uuid' => $uuid,
        );
        $response = $this->request(self::API_URL . 'checkUUID', $options);
        if (!is_object($response)) {
            return false;
        } else {
            return ($response->status == 200);
        }
    }

    protected function request($url, $data = array(), $is_post = false)
    {
        $data = http_build_query($data);
        $options = array(
            CURLOPT_POST => $is_post,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
        );
        if ($is_post) {
            $options[CURLOPT_POSTFIELDS] = $data;
        } elseif ($data) {
            $url = "{$url}?{$data}";
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new \ErrorException(curl_error($curl));
        }
        curl_close($curl);

        return json_decode($response);
    }

    protected function send($channel, $username, $message, $parent_thread = null)
    {
        $response = array(
            'id' => time(),
            'type' => 'message',
            'channel' => $channel,
            'text' => (is_string($username) ? '<@'.$username.'> ' : '') . $message,
        );
        if ($parent_thread) {
            $response['thread_ts'] = $parent_thread;
        }
        $client = $this->getClient();
        $client->send(json_encode($response));
    }
}
