<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Meilisearch;

use App\Modules\Economy\Domain\DTO\Offer;
use App\Modules\Shared\Domain\Model\Searchable;
use Meilisearch\Client;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

readonly class SearchService
{
	private Client $meilisearchClient;

	/**
	 * @var list<class-string<Searchable>>
	 */
	private const INDEXES = [
		Offer::class,
	];

	public function __construct(
		ClientInterface $meilisearchClient,
		#[Autowire('%env(MEILISEARCH_URL)%')]
		string $meilisearchUrl,
		#[Autowire('%env(MEILISEARCH_API_KEY)%')]
		string $meilisearchApiKey,
	) {

		$this->meilisearchClient = new Client($meilisearchUrl, $meilisearchApiKey, $meilisearchClient);
	}

	/**
	 * @return \Generator<array>
	 * @throws \Meilisearch\Exceptions\ApiException
	 */
	public function index(): \Generator
	{
		foreach (self::INDEXES as $indexable) {
			$index = $this->meilisearchClient->index($indexable::getIndex());

			yield $index->create($indexable::getIndex());
		}
	}

	public function store(Searchable $object): void
	{
		$index = $this->meilisearchClient->index($object::getIndex());

		$index->create($object::getIndex());
	}
}
