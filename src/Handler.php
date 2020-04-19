<?php

namespace Albakov\JoditFilebrowser;

use Albakov\JoditFilebrowser\Controllers\Action;

class Handler extends Action
{
    /**
     * @var string[] $approvedActions
     */
    public $approvedActions = [
        'folders',
        'folderRename',
        'folderRemove',
        'folderCreate',
        'folderMove',
        'files',
        'fileUpload',
        'fileRemove',
        'fileRename',
        'fileMove',
        'imageResize',
        'imageCrop',
        'permissions',
    ];

    /**
     * Handle request
     */
    public function handle()
    {
        $this->callAction();
        $this->setSuccessStatus();

        return $this->json();
    }

    /**
     * @return array|mixed
     */
    protected function callAction()
    {
        return in_array($this->request->action, $this->approvedActions) ?
            call_user_func_array([$this, $this->request->action], []) : [];
    }

    /**
     * Set status code and message success
     */
    protected function setSuccessStatus()
    {
        $this->response['data']['code'] = 200;
        $this->response['success'] = true;
    }

    /**
     * Response in json
     */
    protected function json()
    {
        header('Content-Type: application/json');

        echo json_encode($this->response);

        die();
    }
}
