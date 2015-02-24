<?php

namespace PPP\Wikidata\TreeSimplifier;

use PPP\Module\TreeSimplifier\IntersectionNodeSimplifier;
use PPP\Module\TreeSimplifier\NodeSimplifierFactory;
use PPP\Wikidata\ValueParsers\ResourceListNodeParser;
use PPP\Wikidata\ValueParsers\WikibaseValueParserFactory;
use Wikibase\EntityStore\EntityStore;

/**
 * Build a SentenceTreeSimplifier
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 *
 * @todo tests
 */
class WikibaseNodeSimplifierFactory extends NodeSimplifierFactory {

	/**
	 * @param EntityStore $entityStore
	 * @param string $languageCode
	 */
	public function __construct(EntityStore $entityStore, $languageCode) {
		parent::__construct(array(
			$this->newSentenceNodeSimplifier($entityStore, $languageCode),
			$this->newMeaninglessPredicateTripleNodeSimplifier($entityStore, $languageCode),
			$this->newMissingObjectTripleNodeSimplifier($entityStore, $languageCode),
			$this->newMissingSubjectTripleNodeSimplifier($entityStore, $languageCode),
			$this->newIntersectionWithFilterNodeSimplifier($entityStore, $languageCode)
		));
	}

	private function newSentenceNodeSimplifier(EntityStore $entityStore, $languageCode) {
		return new SentenceNodeSimplifier($this->newResourceListNodeParser($entityStore, $languageCode));
	}

	private function newMeaninglessPredicateTripleNodeSimplifier(EntityStore $entityStore, $languageCode) {
		return new SpecificTripleNodeSimplifier($this->newResourceListNodeParser($entityStore, $languageCode));
	}

	private function newMissingObjectTripleNodeSimplifier(EntityStore $entityStore, $languageCode) {
		return new MissingObjectTripleNodeSimplifier(
			$this->newResourceListNodeParser($entityStore, $languageCode),
			$entityStore
		);
	}

	private function newMissingSubjectTripleNodeSimplifier(EntityStore $entityStore, $languageCode) {
		return new MissingSubjectTripleNodeSimplifier(
			$this,
			$entityStore,
			$this->newResourceListNodeParser($entityStore, $languageCode)
		);
	}

	private function newIntersectionWithFilterNodeSimplifier(EntityStore $entityStore, $languageCode) {
		return new IntersectionWithFilterNodeSimplifier(
			new IntersectionNodeSimplifier($this),
			$entityStore,
			$this->newResourceListNodeParser($entityStore, $languageCode)
		);
	}

	private function newResourceListNodeParser(EntityStore $entityStore, $languageCode) {
		$parserFactory = new WikibaseValueParserFactory($languageCode, $entityStore);
		return new ResourceListNodeParser($parserFactory->newWikibaseValueParser());
	}
}
