<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\FcmService;

class SendFcmPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $title;
    protected $body;
    protected $tokens;
    protected $task;
    protected $action;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($title, $body, $tokens, $task, $action)
    {
        $this->title = $title;
        $this->body = $body;
        $this->tokens = $tokens;
        $this->task = $task;
        $this->action = $action;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FcmService $fcmService)
    {
        $fcmService->sendNotification($this->title, $this->body, $this->tokens, $this->task, $this->action);
    }
}
