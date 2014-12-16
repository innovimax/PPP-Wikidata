<?php

namespace PPP\Wikidata\ValueParsers;

use Mediawiki\Api\MediawikiApi;
use PPP\Wikidata\Cache\WikibaseEntityIdParserCache;
use PPP\Wikidata\WikibaseEntityProvider;
use ValueParsers\ParserOptions;
use ValueParsers\StringValueParser;
use ValueParsers\ValueParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;

/**
 * Try to find a Wikibase entity id from a given string. Only returns the first id found.
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class WikibaseEntityIdParser extends StringValueParser {

	const FORMAT_NAME = 'wikibase-entity';

	private static $INSTANCES_TO_FILTER = array('Q4167410', 'Q17362920', 'Q4167836', 'Q13406463', 'Q11266439', 'Q14204246');

	const INSTANCEOF_PID = 'P31';

	/**
	 * Identifier for the option that holds the type of entity the parser should looks for.
	 */
	const OPT_ENTITY_TYPE = 'type';

	/**
	 * @var MediawikiApi
	 */
	private $api;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var WikibaseEntityProvider
	 */
	private $entityProvider;

	/**
	 * @var WikibaseEntityIdParserCache
	 */
	private $entityIdParserCache;

	/**
	 * @param MediaWikiApi $api
	 * @param EntityIdParser $entityIdParser
	 * @param WikibaseEntityIdParserCache $entityIdParserCache
	 * @param WikibaseEntityProvider $entityProvider
	 * @param ParserOptions|null $options
	 */
	public function __construct(MediaWikiApi $api, EntityIdParser $entityIdParser, WikibaseEntityIdParserCache $entityIdParserCache, WikibaseEntityProvider $entityProvider, ParserOptions $options = null) {
		$options->requireOption(self::OPT_ENTITY_TYPE);

		$this->api = $api;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdParserCache = $entityIdParserCache;
		$this->entityProvider = $entityProvider;

		parent::__construct($options);
	}

	protected function stringParse($value) {
		if($value === '') {
			return array();
		}

		$languageCode = $this->getOption(ValueParser::OPT_LANG);
		$entityType = $this->getOption(self::OPT_ENTITY_TYPE);

		if($this->entityIdParserCache->contains($value, $entityType, $languageCode)) {
			$result = $this->entityIdParserCache->fetch($value, $entityType, $languageCode);
		} else {
			$result = $this->parseResult($this->doQuery($value, $entityType, $languageCode), $value);
			$this->entityIdParserCache->save($value, $entityType, $languageCode, $result);
		}

		return $result;
	}

	protected function doQuery($search, $entityType, $languageCode) {
		$params = array(
			'search' => $search,
			'language' => $languageCode,
			'type' => $entityType,
			'limit' => 50
		);
		return $this->api->getAction('wbsearchentities', $params);
	}

	private function parseResult(array $result, $search) {
		$search = $this->cleanLabel($search);

		$results = $this->filterResults($result['search'], $search, true);
		if(empty($results)) {
			$results = $this->filterResults($result['search'], $search, false);
		}

		$entityIds = array();
		foreach($results as $entry) {
			$entityIds[] = $this->entityIdParser->parse($entry['id']);
		}
		$this->entityProvider->loadEntities($entityIds);

		return $this->toEntityValues($this->filterDisambiguation($entityIds));
	}

	private function filterResults(array $results, $search, $isStrict) {
		$filtered = array();
		foreach($results as $entry) {
			if($this->doResultsMatch($entry, $search, $isStrict)) {
				$filtered[] = $entry;
			}
		}

		return $filtered;
	}

	private function doResultsMatch(array $entry, $search, $isStrict) {
		if(array_key_exists('aliases', $entry)) {
			foreach($entry['aliases'] as $alias) {
				if($this->areSimilar($this->cleanLabel($alias), $search, $isStrict)) {
					return true;
				}
			}
		}

		return array_key_exists('label', $entry) &&
		$this->areSimilar($this->cleanLabel($entry['label']), $search, $isStrict);
	}

	private function areSimilar($a, $b, $isStrict) {
		if($isStrict) {
			return $a === $b;
		} else {
			//checks if the strings have less than 3 character different and more than 80% percent of characters similar
			return similar_text($a, $b, $percentage) - strlen($a) < 3 &&
				$percentage > 80;
		}
	}

	private function cleanLabel($label) {
		$label = mb_strtolower($label, 'UTF-8');
		$label = preg_replace('/(\(.*\))/', '', $label); //Removes comments
		$label = str_replace(
			array('\'', '-'),
			array(' ', ' '),
			$label
		);
		return trim($label);
	}

	/**
	 * @param EntityId[] $entityIds
	 * @return array
	 */
	private function filterDisambiguation(array $entityIds) {
		$filtered = array();

		foreach($entityIds as $entityId) {
			if($entityId instanceof ItemId) {
				$item = $this->entityProvider->getItem($entityId);
				if(!$this->isDisambiguation($item)) {
					$filtered[] = $entityId;
				}
			} else {
				$filtered[] = $entityId;
			}
		}

		return $filtered;
	}

	private function isDisambiguation(Item $item) {
		/** @var Statement $statement */
		foreach($item->getStatements()->getWithPropertyId(new PropertyId(self::INSTANCEOF_PID)) as $statement) {
			$mainSnak = $statement->getMainSnak();
			if(
				$mainSnak instanceof PropertyValueSnak &&
				$mainSnak->getDataValue() instanceof EntityIdValue &&
				in_array($mainSnak->getDataValue()->getEntityId()->getSerialization(), self::$INSTANCES_TO_FILTER)
			) {
				return true;
			}
		}

		return false;
	}

	private function toEntityValues(array $entityIds) {
		$entityValues = array();

		foreach($entityIds as $entityId) {
			$entityValues[] = new EntityIdValue($entityId);
		}

		return $entityValues;
	}
}
