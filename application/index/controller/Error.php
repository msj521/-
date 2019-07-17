<?php
namespace app\index\controller;

use think\Controller;
use think\Request;

class Error extends Common
{
    public function index()
    {
        $this->redirect('index/index');
    }

}