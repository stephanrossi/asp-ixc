<?php

protected function schedule(Schedule $schedule)
{
    $schedule->command('ixc:contrato:handle')->everyTwoMinutes();
}
