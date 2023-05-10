<?php

namespace YSTV\NumberedHeadingRefs;

use MediaWiki\Hook\GetDoubleUnderscoreIDsHook;
use MediaWiki\Hook\ParserAfterParseHook;
use MediaWiki\Logger\LoggerFactory;

class NumberedHeadingRefsHooks implements GetDoubleUnderscoreIDsHook, ParserAfterParseHook
{
	public const MAGIC_WORD_NHR = 'NUMBEREDHEADINGREFS';
	private \Psr\Log\LoggerInterface $logger;

	public function __construct()
	{
		$this->logger = LoggerFactory::getInstance( 'NumberedHeadingRefs' );
	}


	public function onGetDoubleUnderscoreIDs( &$doubleUnderscoreIDs ) {
		$doubleUnderscoreIDs[] = self::MAGIC_WORD_NHR;
	}

	public function onParserAfterParse( $parser, &$text, $stripState ) {
		// Using getProperty rather than getPageProperty for MW <1.38 compatibility
		$propVal = $parser->getOutput()->getProperty(self::MAGIC_WORD_NHR);
		if ( $propVal === null || $propVal === false ) {
			return;
		}

		// parsing HTML using regex, woooooo
		if (preg_match_all('|(<a.*?>)#(.+)</a>|i', $text, $matches, PREG_SET_ORDER) === false) {
			$this->logger->error('Failed to find anchors: ' . preg_last_error_msg());
			return;
		}

		$headings = $parser->getOutput()->getSections();

		foreach ($matches as $match) {
			$sectionName = $match[2];
			// TODO: quadratic
			foreach ($headings as $head) {
				if ($head['line'] === $sectionName) {
					$text = str_replace($match[0], sprintf('%s%s</a>', $match[1], $head['number']), $text);
				}
			}
		}
	}
}
