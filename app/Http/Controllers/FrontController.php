<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SergiX44\Nutgram\Nutgram;

class FrontController extends Controller
{
    public function __invoke(Nutgram $bot)
    {
        $bot->run();
    }
}
