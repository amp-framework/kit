<?php

declare(strict_types=1);

namespace ampf\Kit\Tests\Fixtures\App\Controller\Http;

use ampf\BeanAccess\Doctrine\DoctrineEntityManagerAccess;
use ampf\Controller\Http\AbstractController;

/** The page of the notes: how many there are, which takes the request's own entity manager. */
final class NotesController extends AbstractController
{
    use DoctrineEntityManagerAccess;

    public function execute(): void
    {
        $count = $this->getDoctrineEntityManager()->getConnection()->fetchOne('SELECT COUNT(*) FROM notes');

        $this->getRequest()->setResponse('Notes: ' . (is_scalar($count) ? (string)$count : '?'));
    }
}
