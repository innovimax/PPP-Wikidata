<?php

namespace PPP\Wikidata\TreeSimplifier;

use DataValues\BooleanValue;
use DataValues\DecimalValue;
use DataValues\GlobeCoordinateValue;
use DataValues\LatLongValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use PPP\DataModel\MissingNode;
use PPP\DataModel\ResourceListNode;
use PPP\DataModel\TripleNode;
use PPP\Module\TreeSimplifier\NodeSimplifierFactory;
use PPP\Wikidata\WikibaseResourceNode;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use WikidataQueryApi\Query\AbstractQuery;
use WikidataQueryApi\Query\AroundQuery;
use WikidataQueryApi\Query\BetweenQuery;
use WikidataQueryApi\Query\ClaimQuery;
use WikidataQueryApi\Query\QuantityQuery;
use WikidataQueryApi\Query\StringQuery;

/**
 * @covers PPP\Wikidata\TreeSimplifier\MissingSubjectTripleNodeSimplifier
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class MissingSubjectTripleNodeSimplifierTest extends NodeSimplifierBaseTest {

	public function buildSimplifier() {
		$queryServiceMock = $this->getMockBuilder('WikidataQueryApi\Services\SimpleQueryService')
			->disableOriginalConstructor()
			->getMock();
		return new MissingSubjectTripleNodeSimplifier($queryServiceMock);
	}

	public function simplifiableProvider() {
		return array(
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P214'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new StringValue('113230702'))))
				)
			),
		);
	}

	public function nonSimplifiableProvider() {
		return array(
			array(
				new MissingNode()
			),
			array(
				new TripleNode(
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))))),
					new MissingNode(),
						new ResourceListNode(array(new WikibaseResourceNode('', new StringValue('113230702'))))
				)
			),
			array(
				new TripleNode(
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P214'))))),
					new MissingNode()
				)
			),
		);
	}

	/**
	 * @dataProvider simplifiedTripleProvider
	 */
	public function testSimplify(TripleNode $queryNode, ResourceListNode $responseNodes, AbstractQuery $query, array $queryResult) {
		$queryServiceMock = $this->getMockBuilder('WikidataQueryApi\Services\SimpleQueryService')
			->disableOriginalConstructor()
			->getMock();
		$queryServiceMock->expects($this->any())
			->method('doQuery')
			->with($this->equalTo($query))
			->will($this->returnValue($queryResult));

		$simplifier = new MissingSubjectTripleNodeSimplifier($queryServiceMock);
		$this->assertEquals(
			$responseNodes,
			$simplifier->simplify($queryNode)
		);
	}

	public function simplifiedTripleProvider() {
		return array(
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P625'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new GlobeCoordinateValue(new LatLongValue(45.75972, 4.8422), 0.0002777))))
				),
				new ResourceListNode(array(
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q456'))))),
				)),
				new AroundQuery(
					new PropertyId('P625'),
					new LatLongValue(45.75972, 4.8422),
					0.027769999999999996
				),
				array(
					new ItemId('Q456')
				)
			),
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P1082'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new QuantityValue(new DecimalValue('+491268'), '1', new DecimalValue('+491268'), new DecimalValue('+491267')))))
				),
				new ResourceListNode(array(
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q456')))))
				)),
				new QuantityQuery(new PropertyId('P1082'), new DecimalValue('+491268')),
				array(
					new ItemId('Q456')
				)
			),
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P214'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new StringValue('113230702'))))
				),
				new ResourceListNode(array(
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42')))))
				)),
				new StringQuery(new PropertyId('P214'), new StringValue('113230702')),
				array(
					new ItemId('Q42')
				)
			),
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P569'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new TimeValue('+00000001952-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, ''))))
				),
				new ResourceListNode(array(
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42')))))
				)),
				new BetweenQuery(
					new PropertyId('P569'),
					new TimeValue('+00000001952-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, ''),
					new TimeValue('+00000001952-03-11T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, '')
				),
				array(
					new ItemId('Q42')
				)
			),
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P19'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q350')))))
				),
				new ResourceListNode(array(
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42')))))
				)),
				new ClaimQuery(new PropertyId('P19'), new ItemId('Q350')),
				array(
					new ItemId('Q42')
				)
			),
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P19'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q350')))))
				),
				new ResourceListNode(array()),
				new ClaimQuery(new PropertyId('P19'), new ItemId('Q350')),
				array()
			),
		);
	}


	/**
	 * @dataProvider notSimplifiedTripleProvider
	 */
	public function testSimplifyWithException(TripleNode $queryNode, AbstractQuery $query = null, array $queryResult = array()) {
		$queryServiceMock = $this->getMockBuilder( 'WikidataQueryApi\Services\SimpleQueryService' )
			->disableOriginalConstructor()
			->getMock();
		$queryServiceMock->expects($this->any())
			->method('doQuery')
			->with($this->equalTo($query))
			->will($this->returnValue($queryResult));

		$simplifier = new MissingSubjectTripleNodeSimplifier($queryServiceMock);

		$this->setExpectedException('PPP\Module\TreeSimplifier\NodeSimplifierException');
		$simplifier->simplify($queryNode);
	}

	public function notSimplifiedTripleProvider() {
		return array(
			array(
				new TripleNode(
					new MissingNode(),
					new ResourceListNode(array(new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P1'))))),
					new ResourceListNode(array(new WikibaseResourceNode('', new BooleanValue(true))))
				)
			),
		);
	}
}
