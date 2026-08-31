<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Techysavvy\Core\ToolRegistry;

class HomeController extends Controller
{
    public function __invoke(ToolRegistry $toolRegistry): View
    {
        return view('home', [
            'tools' => $toolRegistry->all(),
        ]);
    }
}
