<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTaskMail extends Notification implements ShouldQueue
{
    use Queueable;
    protected $task, $assignee, $assignor;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($task, $assignee, $assignor)
    {
        $this->task = $task;
        $this->assignee = $assignee;
        $this->assignor = $assignor;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->view(
            'emails.newTask',
            ['name' => $this->assignee, 'title' => $this->task->title, 'assignor' => $this->assignor, 'date' => $this->task->due_date]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
