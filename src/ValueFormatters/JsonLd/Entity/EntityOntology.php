<?php

namespace PPP\Wikidata\ValueFormatters\JsonLd\Entity;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\StatementListProvider;

/**
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class EntityOntology {

	const OWL_EQUIVALENT_PROPERTY = 'http://www.w3.org/2002/07/owl#equivalentProperty';

	/**
	 * @var PropertyId[]
	 */
	private $propertyForIris;

	/**
	 * @param PropertyId[] $propertyForIris
	 */
	public function __construct(array $propertyForIris) {
		$this->propertyForIris = $propertyForIris;
	}

	/**
	 * @param Property $property
	 * @return string[]
	 */
	public function getEquivalentPropertiesIris(Property $property) {
		return $this->getTextualContentForIri($property, self::OWL_EQUIVALENT_PROPERTY);
	}

	private function getTextualContentForIri(StatementListProvider $statementListProvider, $iri) {
		if(!array_key_exists($iri, $this->propertyForIris)) {
			return array();
		}

		return $this->getTextualContentForProperty($statementListProvider, $this->propertyForIris[$iri]);
	}

	private function getTextualContentForProperty(StatementListProvider $statementListProvider, PropertyId $propertyId) {
		$snaks = $statementListProvider->getStatements()->getWithPropertyId($propertyId)->getMainSnaks();

		$iris = array();
		foreach($snaks as $snak) {
			if($snak instanceof PropertyValueSnak) {
				$dataValue = $snak->getDataValue();

				if($dataValue instanceof StringValue) {
					$iris[] = $snak->getDataValue()->getValue();
				}
			}
		}

		return $iris;
	}
}
