<?php

use EvolutionCMS\Facades\ManagerTheme;

// get the duplicate processor
extract(ManagerTheme::getViewAttributes());
include_once ManagerTheme::getFileProcessor('processors/duplicate_htmlsnippet.processor.php');
