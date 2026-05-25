<?php

declare(strict_types=1);

namespace App\Modules\Hermes\Infrastructure\Controller\News;

use App\Modules\Hermes\Domain\Repository\NewsRepositoryInterface;
use App\Modules\Zeus\Domain\Repository\PlayerRepositoryInterface;
use App\Modules\Zeus\Model\Player;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
	path: '/news',
	name: 'view_news',
	methods: [Request::METHOD_GET],
)]
class ViewNews extends AbstractController
{
	public function __invoke(
		Player $currentPlayer,
		ClockInterface $clock,
		NewsRepositoryInterface $newsRepository,
		PlayerRepositoryInterface $playerRepository,
	): Response {
		$currentPlayer->lastReadNewsAt = $clock->now();

		$playerRepository->save($currentPlayer);

		return $this->render('pages/hermes/news.html.twig', [
			'news' => $newsRepository->getAll($currentPlayer->faction),
		]);
	}
}
