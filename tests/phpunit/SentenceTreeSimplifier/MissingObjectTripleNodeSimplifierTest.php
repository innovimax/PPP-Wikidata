<?php

namespace PPP\Wikidata\SentenceTreeSimplifier;

use DataValues\StringValue;
use Mediawiki\DataModel\Revision;
use PPP\DataModel\AbstractNode;
use PPP\DataModel\MissingNode;
use PPP\DataModel\TripleNode;
use PPP\Wikidata\WikibaseResourceNode;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\ItemContent;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers PPP\Wikidata\SentenceTreeSimplifier\MissingObjectTripleNodeSimplifier
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class MissingObjectTripleNodeSimplifierTest extends NodeSimplifierBaseTest {

	/**
	 * @see NodeSimplifierBaseTest::NodeSimplifierBaseTest
	 */
	public function buildDummySimplifier() {
		$revisionGetterMock = $this->getMockBuilder( 'Wikibase\Api\Service\RevisionGetter' )
			->disableOriginalConstructor()
			->getMock();
		return new MissingObjectTripleNodeSimplifier($revisionGetterMock);
	}

	/**
	 * @see NodeSimplifierBaseTest::simplifiableProvider
	 */
	public function simplifiableProvider() {
		return array(
			array(
				new TripleNode(
					new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))),
					new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P214'))),
					new MissingNode()
				)
			),
		);
	}

	/**
	 * @see NodeSimplifierBaseTest::nonSimplifiableProvider
	 */
	public function nonSimplifiableProvider() {
		return array(
			array(
				new MissingNode()
			),
			array(
				new TripleNode(
					new MissingNode(),
					new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P214'))),
					new WikibaseResourceNode('', new StringValue('113230702'))
				)
			),
			array(
				new TripleNode(
					new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))),
					new MissingNode(),
					new WikibaseResourceNode('', new StringValue('113230702'))
				)
			),
		);
	}

	/**
	 * @dataProvider simplifiedTripleProvider
	 */
	public function testSimplify(TripleNode $queryNode, AbstractNode $responseNode, Item $item) {
		$revisionGetterMock = $this->getMockBuilder( 'Wikibase\Api\Service\RevisionGetter' )
			->disableOriginalConstructor()
			->getMock();
		$revisionGetterMock->expects($this->any())
			->method('getFromId')
			->with($this->equalTo(new ItemId('Q42')))
			->will($this->returnValue(new Revision(new ItemContent($item))));

		$simplifier = new MissingObjectTripleNodeSimplifier($revisionGetterMock);
		$this->assertEquals(
			$responseNode,
			$simplifier->simplify($queryNode)
		);
	}

	public function simplifiedTripleProvider() {
		$list = array();

		//Value
		$douglasAdamItem = Item::newEmpty();
		$douglasAdamItem->setId(new ItemId('Q42'));
		$birthPlaceStatement = new Statement(
			new PropertyValueSnak(new PropertyId('P214'), new StringValue('113230702'))
		);
		$birthPlaceStatement->setGuid('42');
		$douglasAdamItem->addClaim($birthPlaceStatement);
		$list[] = array(
			new TripleNode(
				new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))),
				new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P214'))),
				new MissingNode()
			),
			new WikibaseResourceNode('', new StringValue('113230702')),
			$douglasAdamItem
		);

		//SomeValue
		$douglasAdamItem = Item::newEmpty();
		$douglasAdamItem->setId(new ItemId('Q42'));
		$birthPlaceStatement = new Statement(new PropertySomeValueSnak(new PropertyId('P19')));
		$birthPlaceStatement->setGuid('42');
		$douglasAdamItem->addClaim($birthPlaceStatement);
		$list[] = array(
			new TripleNode(
				new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))),
				new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P19'))),
				new MissingNode()
			),
			new MissingNode(),
			$douglasAdamItem
		);

		return $list;
	}


	/**
	 * @dataProvider notSimplifiedTripleProvider
	 */
	public function testSimplifyWithException(TripleNode $queryNode, $revision) {
		$revisionGetterMock = $this->getMockBuilder( 'Wikibase\Api\Service\RevisionGetter' )
			->disableOriginalConstructor()
			->getMock();
		$revisionGetterMock->expects($this->any())
			->method('getFromId')
			->with($this->equalTo(new ItemId('Q42')))
			->will($this->returnValue($revision));

		$simplifier = new MissingObjectTripleNodeSimplifier($revisionGetterMock);

		$this->setExpectedException('PPP\Wikidata\SentenceTreeSimplifier\SimplifierException');
		$simplifier->simplify($queryNode);
	}

	public function notSimplifiedTripleProvider() {
		$list = array();

		//NoValue
		$douglasAdamItem = Item::newEmpty();
		$douglasAdamItem->setId(new ItemId('Q42'));
		$birthPlaceStatement = new Statement(new PropertyNoValueSnak(new PropertyId('P19')));
		$birthPlaceStatement->setGuid('42');
		$douglasAdamItem->addClaim($birthPlaceStatement);
		$list[] = array(
			new TripleNode(
				new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))),
				new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P19'))),
				new MissingNode()
			),
			new Revision(new ItemContent($douglasAdamItem))
		);

		//no Snak
		$douglasAdamItem = Item::newEmpty();
		$douglasAdamItem->setId(new ItemId('Q42'));
		$list[] = array(
			new TripleNode(
				new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))),
				new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P19'))),
				new MissingNode()
			),
			new Revision(new ItemContent($douglasAdamItem))
		);

		//no Item
		$douglasAdamItem = Item::newEmpty();
		$douglasAdamItem->setId(new ItemId('Q42'));
		$list[] = array(
			new TripleNode(
				new WikibaseResourceNode('', new EntityIdValue(new ItemId('Q42'))),
				new WikibaseResourceNode('', new EntityIdValue(new PropertyId('P19'))),
				new MissingNode()
			),
			false
		);

		return $list;
	}
}
