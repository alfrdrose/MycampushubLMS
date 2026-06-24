<?php

namespace App\Enums;

enum QuizStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case CLOSED = 'closed';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';
    case UNPUBLISHED = 'unpublished';
}
