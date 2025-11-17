<?php

namespace App\Enums;

enum TelegramStep: string
{
    case IDLE = 'idle';
    case SELECT_STORE = 'select_store';
    case SELECT_CATEGORY = 'select_category';
    case SELECT_PROBLEM = 'select_problem';
    case ENTER_DESCRIPTION = 'enter_description';
    case UPLOAD_FILE = 'upload_file';
    case CONFIRM = 'confirm';

    public function next(): ?self
    {
        return match($this) {
            self::IDLE => self::SELECT_STORE,
            self::SELECT_STORE => self::SELECT_CATEGORY,
            self::SELECT_CATEGORY => self::SELECT_PROBLEM,
            self::SELECT_PROBLEM => self::ENTER_DESCRIPTION,
            self::ENTER_DESCRIPTION => self::UPLOAD_FILE,
            self::UPLOAD_FILE => self::CONFIRM,
            self::CONFIRM => self::IDLE,
        };
    }
}
