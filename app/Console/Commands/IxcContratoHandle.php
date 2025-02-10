<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\IxcContratoController;

class IxcContratoHandle extends Command
{
    /**
     * A assinatura e o nome do comando no terminal.
     *
     * @var string
     */
    protected $signature = 'ixc:contrato:handle';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Executa o método handle do IxcContratoController';

    /**
     * Executa o comando.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new IxcContratoController();
        $controller->handle();

        $this->info('Método handle executado com sucesso.');

        return 0;
    }
}
