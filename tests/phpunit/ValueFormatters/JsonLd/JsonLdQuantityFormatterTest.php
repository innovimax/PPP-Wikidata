<?php

namespace PPP\Wikidata\ValueFormatters\JsonLd;

use DataValues\DecimalValue;
use DataValues\QuantityValue;
use ValueFormatters\DecimalFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\QuantityFormatter;
use ValueFormatters\Test\ValueFormatterTestBase;

/**
 * @covers PPP\Wikidata\ValueFormatters\JsonLd\JsonLdQuantityFormatter
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class JsonLdQuantityFormatterTest extends ValueFormatterTestBase {

	/**T
	 * @see ValueFormatterTestBase::validProvider
	 */
	public function validProvider() {
		return array(
			array(
				new QuantityValue(new DecimalValue(1234), '1', new DecimalValue(1235), new DecimalValue(1233.3333)),
				(object) array(
					'@type' => 'QuantitativeValue',
					'name' => '1234.0±1.0',
					'value' => (object) array('@type' => 'Integer', '@value' => 1234),
					'minValue' => (object) array('@type' => 'Float', '@value' => 1233.3333),
					'maxValue' => (object) array('@type' => 'Integer', '@value' => 1235),
				)
			),
		);
	}

	/**
	 * @see ValueFormatterTestBase::getFormatterClass
	 *
	 * @return string
	 */
	protected function getFormatterClass() {
		return 'PPP\Wikidata\ValueFormatters\JsonLd\JsonLdQuantityFormatter';
	}

	/**
	 * @see ValueFormatterTestBase::getInstance
	 */
	protected function getInstance(FormatterOptions $options) {
		$class = $this->getFormatterClass();

		return new $class(
			new QuantityFormatter(new DecimalFormatter($options), $options),
			new JsonLdDecimalFormatter($options),
			$options
		);
	}
}
