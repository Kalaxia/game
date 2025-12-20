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
	private const INDEXABLES = [
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
		foreach (self::INDEXABLES as $indexable) {
			$index = $this->meilisearchClient->index($indexable::getIndex());

			$index->create($indexable::getIndex());
			$index->updateSettings($indexable::getIndexSettings());

			yield $index;
		}
	}

	public function store(Searchable $object): void
	{
		$index = $this->meilisearchClient->index($object::getIndex());

		$index->updateDocuments([$object->getSearchableData()]);
	}

	public function search(string $index, array $params): array
	{
		$index = $this->meilisearchClient->index($index);

		$result = $index->search(
			searchParams: $params,
		);

		return $result->getHits();
	}

	public function truncateIndex(string $index): void
	{
		$index = $this->meilisearchClient->index($index);

		$index->deleteAllDocuments();
	}

	public function truncateAllIndexes(): void
	{
		foreach (self::INDEXABLES as $indexable) {
			$this->truncateIndex($indexable::getIndex());
		}
	}
}
