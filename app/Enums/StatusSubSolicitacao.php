<?php

namespace App\Enums;

enum StatusSubSolicitacao: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Retrying = 'retrying';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Running => 'Em execução',
            self::Completed => 'Concluída',
            self::Failed => 'Falhou',
            self::Retrying => 'Retentando',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Running => 'blue',
            self::Completed => 'green',
            self::Failed => 'red',
            self::Retrying => 'yellow',
        };
    }
}
