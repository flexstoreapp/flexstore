<?php

declare(strict_types=1);

use App\Actions\ConsumeOrderItemDownloadAction;

covers(ConsumeOrderItemDownloadAction::class);

uses()->group('actions', 'downloads');
