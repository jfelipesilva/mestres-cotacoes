<?php

namespace App\Enums;

enum StatusSolicitacao: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Partial = 'partial';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processing => 'Processando',
            self::Completed => 'Concluída',
            self::Partial => 'Parcial',
            self::Failed => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Processing => 'blue',
            self::Completed => 'green',
            self::Partial => 'yellow',
            self::Failed => 'red',
        };
    }
}
