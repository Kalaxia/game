<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Controller;

use App\Modules\Economy\Domain\Repository\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route(
	path: '/economy/market/command-product',
	name: 'command_product',
	methods: [Request::METHOD_POST],
)]
class CommandProduct extends AbstractController
{
	public function __invoke(
		Request $request,
		ProductRepositoryInterface $productRepository,
	): Response {
		$productId = $request->request->getString('product-id')
			?? throw new BadRequestHttpException('Product id is required');
		$quantity = $request->request->getInt('quantity')
			?? throw new BadRequestHttpException('Quantity is required');

		$product = $productRepository->get(Uuid::fromString($productId))
			?? throw new NotFoundHttpException('Product not found');

		dd($product);

		return $this->redirectToRoute('view_market');
	}
}
