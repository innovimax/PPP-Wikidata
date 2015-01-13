<?php

namespace PPP\Wikidata\ValueFormatters;

use InvalidArgumentException;
use OutOfBoundsException;
use PPP\DataModel\JsonLdResourceNode;
use PPP\Wikidata\WikibaseEntityProvider;
use PPP\Wikidata\Wikipedia\MediawikiArticleImageProvider;
use stdClass;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\Term;

/**
 * Returns the label of a given Wikibase entity id
 *
 * @licence GPLv2+
 * @author Thomas Pellissier Tanon
 */
class WikibaseEntityIdFormatter extends ValueFormatterBase {

	/**
	 * @var WikibaseEntityProvider
	 */
	private $entityProvider;

	/**
	 * @var MediawikiArticleImageProvider
	 */
	private $mediawikiArticleImageProvider;

	/**
	 * @param WikibaseEntityProvider $entityProvider
	 * @param FormatterOptions $options
	 */
	public function __construct(WikibaseEntityProvider $entityProvider, MediawikiArticleImageProvider $mediawikiArticleImageProvider, FormatterOptions $options) {
		$this->entityProvider = $entityProvider;
		$this->mediawikiArticleImageProvider = $mediawikiArticleImageProvider;
		parent::__construct($options);
	}

	/**
	 * @see ValueFormatter::format
	 */
	public function format($value) {
		if(!($value instanceof EntityIdValue)) {
			throw new InvalidArgumentException('$value should be a DataValue');
		}

		$entity = $this->entityProvider->getEntityDocument($value->getEntityId());
		$stringAlternative = $entity->getId()->getSerialization();

		$resource = new stdClass();
		$resource->{'@context'} = 'http://schema.org';
		$resource->{'@type'} = 'Thing';
		$resource->{'@id'} = 'http://www.wikidata.org/entity/' . $value->getEntityId()->getSerialization(); //TODO: option

		if($entity instanceof FingerprintProvider) {
			$this->addFingerprintToResource($entity->getFingerprint(), $resource);
			$stringAlternative = $this->getLabelFromFingerprint($entity->getFingerprint());
		}

		if($entity instanceof Item) {
			$this->addImageToResource($entity->getSiteLinkList(), $resource);
		}

		return new JsonLdResourceNode(
			$stringAlternative,
			$resource
		);
	}

	private function getLabelFromFingerprint(Fingerprint $fingerprint) {
		try {
			return $fingerprint->getLabel($this->getOption(ValueFormatter::OPT_LANG))->getText();
		} catch(OutOfBoundsException $e) {
			return '';
		}
	}

	private function addFingerprintToResource(Fingerprint $fingerprint, stdClass $resource) {
		$languageCode = $this->getOption(ValueFormatter::OPT_LANG);

		try {
			$resource->name = $this->newResourceFromTerm($fingerprint->getLabel($languageCode));
		} catch(OutOfBoundsException $e) {
		}

		try {
			$resource->description = $this->newResourceFromTerm($fingerprint->getDescription($languageCode));
		} catch(OutOfBoundsException $e) {
		}

		try {
			$aliasGroup = $fingerprint->getAliasGroup($languageCode);
			$resource->alternateName = array();
			foreach($aliasGroup->getAliases() as $alias) {
				$resource->alternateName[] = $this->newResourceFromTerm(new Term($aliasGroup->getLanguageCode(), $alias));
			}
		} catch(OutOfBoundsException $e) {
		}
	}

	private function newResourceFromTerm(Term $term) {
		$resource = new stdClass();
		$resource->{'@language'} = $term->getLanguageCode();
		$resource->{'@value'} = $term->getText();
		return $resource;
	}

	/**
	 * @param SiteLinkList $siteLinkList
	 * @param stdClass $resource
	 */
	private function addImageToResource(SiteLinkList $siteLinkList, stdClass $resource) {
		$wikiId = $this->getOption(ValueFormatter::OPT_LANG) . 'wiki';

		if(!$this->mediawikiArticleImageProvider->isWikiIdSupported($wikiId)) {
			return;
		}

		try {
			$image = $this->mediawikiArticleImageProvider->getImageForSiteLink($siteLinkList->getBySiteId($wikiId));

			$resource->image = new stdClass();
			$resource->image->{'@type'} = 'ImageObject';
			$resource->image->{'@id'} = 'http://commons.wikimedia.org/wiki/Image:' . str_replace(' ', '_', $image->getTitle());
			$resource->image->contentUrl = $image->getUrl();
			$resource->image->width = $image->getWidth();
			$resource->image->height = $image->getHeight();
			$resource->image->name = $image->getTitle();
		} catch(OutOfBoundsException $e) {
		}
	}
}
