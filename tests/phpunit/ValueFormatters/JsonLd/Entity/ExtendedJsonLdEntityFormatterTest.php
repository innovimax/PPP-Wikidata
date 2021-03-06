<?php

namespace PPP\Wikidata\ValueFormatters;

use PPP\Wikidata\ValueFormatters\JsonLd\Entity\JsonLdEntityFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\Test\ValueFormatterTestBase;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers PPP\Wikidata\ValueFormatters\JsonLd\Entity\ExtendedJsonLdEntityFormatter
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class ExtendedJsonLdEntityFormatterTest extends ValueFormatterTestBase {

	/**
	 * @see ValueFormatterTestBase::validProvider
	 */
	public function validProvider() {
		return array(
			array(
				$this->getQ42(),
				(object) array(
					'@type' => 'Thing',
					'@id' => 'http://www.wikidata.org/entity/Q42',
					'name' => (object) array('@value' => 'Douglas Adams', '@language' => 'en'),
					'description' => (object) array('@value' => 'Author', '@language' => 'en'),
					'alternateName' => array(
						(object) array('@value' => '42', '@language' => 'en')
					),
					'gender' => array(
						(object) array('name' => 'foo')
					)
				),
				new FormatterOptions(array(
					JsonLdEntityFormatter::OPT_ENTITY_BASE_URI => 'http://www.wikidata.org/entity/'
				))
			),
		);
	}

	/**
	 * @see ValueFormatterTestBase::getFormatterClass
	 */
	protected function getFormatterClass() {
		return 'PPP\Wikidata\ValueFormatters\JsonLd\Entity\ExtendedJsonLdEntityFormatter';
	}

	/**
	 * @see ValueFormatterTestBase::getInstance
	 */
	protected function getInstance(FormatterOptions $options) {
		$class = $this->getFormatterClass();

		$snakFormatterMock = $this->getMock('ValueFormatters\ValueFormatter');
		$snakFormatterMock->expects($this->any())
			->method('format')
			->with($this->equalTo(new PropertyValueSnak(new PropertyId('P21'), new EntityIdValue(new ItemId('Q1')))))
			->willReturn(array(
				'gender' => (object) array('name' => 'foo')
			));

		return new $class(
			new JsonLdEntityFormatter($options),
			$snakFormatterMock,
			$options
		);
	}

	private function getQ42() {
		return new Item(
			new ItemId('Q42'),
			new Fingerprint(
				new TermList(array(new Term('en', 'Douglas Adams'), new Term('ru', 'Дуглас Адамс'))),
				new TermList(array(new Term('en', 'Author'))),
				new AliasGroupList(array(new AliasGroup('en', array('42'))))
			),
			null,
			new StatementList(array(
				new Statement(new Claim(new PropertyValueSnak(new PropertyId('P21'), new EntityIdValue(new ItemId('Q1')))))
			))
		);
	}
}
