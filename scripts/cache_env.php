#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

\helpers\Jobs::env(isset($argv[1]) ? $argv[1] : '.env');
