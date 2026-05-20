<?php

namespace App\Services;

class FcmService
{
    /**
     * Send FCM Push Notification
     *
     * @param string $title
     * @param string $body
     * @param array|string $tokens
     * @param mixed $task
     * @param string $action
     * @return bool|string
     */
    public function sendNotification($title, $body, $tokens, $task, $action)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $FcmToken = is_array($tokens) ? $tokens : [$tokens];
        $serverKey = env('FCM_SERVER_KEY', 'AAAALUm9zs0:APA91bEPvG8yI7CWfmFLKrqEJPDCVNpmDlqrPz1jY62Wq0k7lEakb36Qts8ZvNLSoo5Sh_dc47-H61y2NoZurjY0bV-wms1xk13NHEnIQq771LYXPZtJi_qVPZXmbQzELGEZE7ohTlIL');

        $data = [
            "registration_ids" => $FcmToken,
            "data" => [
                "title" => $title,
                "body" => $body,
                "action" => $action,
            ]
        ];

        if ($task) {
            $from_location = $task->from ?? null;
            $to_location = $task->to ?? null;
            
            if ($from_location && $to_location) {
                $task->from_location_name = $from_location->name;
                $task->to_location_name = $to_location->name;
                
                $data['data']["task_id"] = $task->id;
                $data['data']["from_location_name"] = $from_location->name;
                $data['data']["to_location_name"] = $to_location->name;
                $data['data']["task_type"] = 'task';
                $data['data']["task_object"] = $task;
            }
        }

        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        
        $result = curl_exec($ch);
        
        if ($result === FALSE) {
            \Log::error('FCM Curl failed: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        return $result;
    }
}
