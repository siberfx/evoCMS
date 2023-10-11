<?php

use EvolutionCMS\Facades\ManagerTheme;

// get the mutate page for changing content
extract(ManagerTheme::getViewAttributes());
echo ManagerTheme::view('partials.header')->render();
include_once ManagerTheme::getFileProcessor('actions/mutate_content.dynamic.php');
echo ManagerTheme::view('partials.footer')->render();
