<?php

declare(strict_types=1);

namespace App\Modules\Economy\Infrastructure\Command;

use App\Modules\Economy\Application\Message\ProductOfferIndexMessage;
use App\Modules\Economy\Domain\Entity\Product;
use App\Modules\Economy\Domain\Enum\ProductType;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
	name: 'economy:index-offers',
	description: 'Index offers for market',
)]
class IndexOffers extends Command
{
	public function __construct(
		private readonly EntityManagerInterface $entityManager,
		private readonly MessageBusInterface $messageBus,
	) {
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type of product to index');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$style = new SymfonyStyle($input, $output);

		$productType = (null === ($type = $input->getOption('type')))
			? $this->askProductType($style)
			: ProductType::from($type);

		$classMetadata = $this->entityManager->getClassMetadata(Product::class);

		$productClass = $classMetadata->discriminatorMap[$productType->value];

		foreach ($this->entityManager->getRepository($productClass)->matching(new Criteria()) as $product) {
			$this->messageBus->dispatch(new ProductOfferIndexMessage($product->id));
		}

		return self::SUCCESS;
	}

	private function askProductType(SymfonyStyle $style): ProductType
	{
		return ProductType::from($style->askQuestion(new ChoiceQuestion('What product type ?', array_map(
			fn (ProductType $type) => $type->value,
			ProductType::cases(),
		))));
	}
}
