<?php
/**
 * WordPress Coding Standard.
 *
 * @package WPCS\WordPressCodingStandards
 * @link    https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
 * @license https://opensource.org/licenses/MIT MIT
 */

/**
 * Enforces spacing around logical operators and assignments, based upon Squiz code.
 *
 * @package WPCS\WordPressCodingStandards
 *
 * @since   0.1.0
 * @since   2013-06-11 This sniff no longer supports JS.
 * @since   0.3.0      This sniff now has the ability to fix most errors it flags.
 * @since   0.7.0      This class now extends WordPress_Sniff.
 *
 * Last synced with base class 2017-01-15 at commit b024ad84656c37ef5733c6998ebc1e60957b2277.
 * Note: This class has diverged quite far from the original. All the same, checking occassionally
 * to see if there are upstream fixes made from which this sniff can benefit, is warranted.
 * @link    https://github.com/squizlabs/PHP_CodeSniffer/blob/master/CodeSniffer/Standards/Squiz/Sniffs/WhiteSpace/ControlStructureSpacingSniff.php
 */
class WordPress_Sniffs_WhiteSpace_ControlStructureSpacingSniff extends WordPress_Sniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = array( 'PHP' );

	/**
	 * Check for blank lines on start/end of control structures.
	 *
	 * @var boolean
	 */
	public $blank_line_check = false;

	/**
	 * Check for blank lines after control structures.
	 *
	 * @var boolean
	 */
	public $blank_line_after_check = true;

	/**
	 * Require for space before T_COLON when using the alternative syntax for control structures.
	 *
	 * @var string one of 'required', 'forbidden', optional'
	 */
	public $space_before_colon = 'required';

	/**
	 * How many spaces should be between a T_CLOSURE and T_OPEN_PARENTHESIS.
	 *
	 * `function[*]() {...}`
	 *
	 * @since 0.7.0
	 *
	 * @var int
	 */
	public $spaces_before_closure_open_paren = -1;

	/**
	 * Tokens for which to ignore extra space on the inside of parenthesis.
	 *
	 * For functions, this is already checked by the Squiz.Functions.FunctionDeclarationArgumentSpacing sniff.
	 * For do / else / try, there are no parenthesis, so skip it.
	 *
	 * @since 0.11.0
	 *
	 * @var array
	 */
	private $ignore_extra_space_after_open_paren = array(
		T_FUNCTION => true,
		T_CLOSURE  => true,
		T_DO       => true,
		T_ELSE     => true,
		T_TRY      => true,
	);

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return array(
			T_IF,
			T_WHILE,
			T_FOREACH,
			T_FOR,
			T_SWITCH,
			T_DO,
			T_ELSE,
			T_ELSEIF,
			T_FUNCTION,
			T_CLOSURE,
			T_USE,
			T_TRY,
			T_CATCH,
		);

	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
	 * @param int                  $stackPtr  The position of the current token in the
	 *                                        stack passed in $tokens.
	 *
	 * @return void
	 */
	public function process( PHP_CodeSniffer_File $phpcsFile, $stackPtr ) {
		$this->blank_line_check 	  = (bool) $this->blank_line_check;
		$this->blank_line_after_check = (bool) $this->blank_line_after_check;

		$this->init( $phpcsFile );

		if ( isset( $this->tokens[ ( $stackPtr + 1 ) ] ) && T_WHITESPACE !== $this->tokens[ ( $stackPtr + 1 ) ]['code']
			&& ! ( T_ELSE === $this->tokens[ $stackPtr ]['code'] && T_COLON === $this->tokens[ ( $stackPtr + 1 ) ]['code'] )
			&& ! ( T_CLOSURE === $this->tokens[ $stackPtr ]['code']
				&& 0 >= (int) $this->spaces_before_closure_open_paren )
		) {
			$error = 'Space after opening control structure is required';
			$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceAfterStructureOpen' );

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->addContent( $stackPtr, ' ' );
				$phpcsFile->fixer->endChangeset();
			}
		}

		if ( ! isset( $this->tokens[ $stackPtr ]['scope_closer'] ) ) {

			if ( T_USE === $this->tokens[ $stackPtr ]['code'] && 'closure' === $this->get_use_type( $stackPtr ) ) {
				$scopeOpener = $phpcsFile->findNext( T_OPEN_CURLY_BRACKET, ( $stackPtr + 1 ) );
				$scopeCloser = $this->tokens[ $scopeOpener ]['scope_closer'];
			} elseif ( T_WHILE !== $this->tokens[ $stackPtr ]['code'] ) {
				return;
			}
		} else {
			$scopeOpener = $this->tokens[ $stackPtr ]['scope_opener'];
			$scopeCloser = $this->tokens[ $stackPtr ]['scope_closer'];
		}

		// Alternative syntax.
		if ( isset( $scopeOpener ) && T_COLON === $this->tokens[ $scopeOpener ]['code'] ) {

			if ( 'required' === $this->space_before_colon ) {

				if ( T_WHITESPACE !== $this->tokens[ ( $scopeOpener - 1 ) ]['code'] ) {
					$error = 'Space between opening control structure and T_COLON is required';
					$fix   = $phpcsFile->addFixableError( $error, $scopeOpener, 'NoSpaceBetweenStructureColon' );

					if ( true === $fix ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->addContentBefore( $scopeOpener, ' ' );
						$phpcsFile->fixer->endChangeset();
					}
				}
			} elseif ( 'forbidden' === $this->space_before_colon ) {

				if ( T_WHITESPACE === $this->tokens[ ( $scopeOpener - 1 ) ]['code'] ) {
					$error = 'Extra space between opening control structure and T_COLON found';
					$fix   = $phpcsFile->addFixableError( $error, ( $scopeOpener - 1 ), 'SpaceBetweenStructureColon' );

					if ( true === $fix ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->replaceToken( ( $scopeOpener - 1 ), '' );
						$phpcsFile->fixer->endChangeset();
					}
				}
			}
		} // End if().

		$parenthesisOpener = $phpcsFile->findNext( PHP_CodeSniffer_Tokens::$emptyTokens, ( $stackPtr + 1 ), null, true );

		// If this is a function declaration.
		if ( T_FUNCTION === $this->tokens[ $stackPtr ]['code'] ) {

			if ( T_STRING === $this->tokens[ $parenthesisOpener ]['code'] ) {

				$function_name_ptr = $parenthesisOpener;

			} elseif ( T_BITWISE_AND === $this->tokens[ $parenthesisOpener ]['code'] ) {

				// This function returns by reference (function &function_name() {}).
				$parenthesisOpener = $phpcsFile->findNext(
					PHP_CodeSniffer_Tokens::$emptyTokens,
					( $parenthesisOpener + 1 ),
					null,
					true
				);
				$function_name_ptr = $parenthesisOpener;
			}

			if ( isset( $function_name_ptr ) ) {
				$parenthesisOpener = $phpcsFile->findNext(
					PHP_CodeSniffer_Tokens::$emptyTokens,
					( $parenthesisOpener + 1 ),
					null,
					true
				);

				// Checking this: function my_function[*](...) {}.
				if ( ( $function_name_ptr + 1 ) !== $parenthesisOpener ) {

					$error = 'Space between function name and opening parenthesis is prohibited.';
					$fix   = $phpcsFile->addFixableError(
						$error,
						$stackPtr,
						'SpaceBeforeFunctionOpenParenthesis',
						$this->tokens[ ( $function_name_ptr + 1 ) ]['content']
					);

					if ( true === $fix ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->replaceToken( ( $function_name_ptr + 1 ), '' );
						$phpcsFile->fixer->endChangeset();
					}
				}
			}
		} elseif ( T_CLOSURE === $this->tokens[ $stackPtr ]['code'] ) {

			// Check if there is a use () statement.
			if ( isset( $this->tokens[ $parenthesisOpener ]['parenthesis_closer'] ) ) {

				$usePtr = $phpcsFile->findNext(
					PHP_CodeSniffer_Tokens::$emptyTokens,
					( $this->tokens[ $parenthesisOpener ]['parenthesis_closer'] + 1 ),
					null,
					true,
					null,
					true
				);

				// If it is, we set that as the "scope opener".
				if ( T_USE === $this->tokens[ $usePtr ]['code'] ) {
					$scopeOpener = $usePtr;
				}
			}
		} // End if().

		if (
			T_COLON !== $this->tokens[ $parenthesisOpener ]['code']
			&& T_FUNCTION !== $this->tokens[ $stackPtr ]['code']
		) {

			if (
				T_CLOSURE === $this->tokens[ $stackPtr ]['code']
				&& 0 === (int) $this->spaces_before_closure_open_paren
			) {

				if ( ( $stackPtr + 1 ) !== $parenthesisOpener ) {
					// Checking this: function[*](...) {}.
					$error = 'Space before closure opening parenthesis is prohibited';
					$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'SpaceBeforeClosureOpenParenthesis' );

					if ( true === $fix ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->replaceToken( ( $stackPtr + 1 ), '' );
						$phpcsFile->fixer->endChangeset();
					}
				}
			} elseif (
				(
					T_CLOSURE !== $this->tokens[ $stackPtr ]['code']
					|| 1 === (int) $this->spaces_before_closure_open_paren
				)
				&& ( $stackPtr + 1 ) === $parenthesisOpener
			) {

				// Checking this: if[*](...) {}.
				$error = 'No space before opening parenthesis is prohibited';
				$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceBeforeOpenParenthesis' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->addContent( $stackPtr, ' ' );
					$phpcsFile->fixer->endChangeset();
				}
			}
		} // End if().

		if (
			T_WHITESPACE === $this->tokens[ ( $stackPtr + 1 ) ]['code']
			&& ' ' !== $this->tokens[ ( $stackPtr + 1 ) ]['content']
		) {
			// Checking this: if [*](...) {}.
			$error = 'Expected exactly one space before opening parenthesis; "%s" found.';
			$fix   = $phpcsFile->addFixableError(
				$error,
				$stackPtr,
				'ExtraSpaceBeforeOpenParenthesis',
				$this->tokens[ ( $stackPtr + 1 ) ]['content']
			);

			if ( true === $fix ) {
				$phpcsFile->fixer->beginChangeset();
				$phpcsFile->fixer->replaceToken( ( $stackPtr + 1 ), ' ' );
				$phpcsFile->fixer->endChangeset();
			}
		}

		if ( T_CLOSE_PARENTHESIS !== $this->tokens[ ( $parenthesisOpener + 1 ) ]['code'] ) {
			if ( T_WHITESPACE !== $this->tokens[ ( $parenthesisOpener + 1 ) ]['code'] ) {
				// Checking this: $value = my_function([*]...).
				$error = 'No space after opening parenthesis is prohibited';
				$fix   = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceAfterOpenParenthesis' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->addContent( $parenthesisOpener, ' ' );
					$phpcsFile->fixer->endChangeset();
				}
			} elseif ( ( ' ' !== $this->tokens[ ( $parenthesisOpener + 1 ) ]['content']
				&& "\n" !== $this->tokens[ ( $parenthesisOpener + 1 ) ]['content']
				&& "\r\n" !== $this->tokens[ ( $parenthesisOpener + 1 ) ]['content'] )
				&& ! isset( $this->ignore_extra_space_after_open_paren[ $this->tokens[ $stackPtr ]['code'] ] )
			) {
				// Checking this: if ([*]...) {}.
				$error = 'Expected exactly one space after opening parenthesis; "%s" found.';
				$fix   = $phpcsFile->addFixableError(
					$error,
					$stackPtr,
					'ExtraSpaceAfterOpenParenthesis',
					$this->tokens[ ( $parenthesisOpener + 1 ) ]['content']
				);

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( ( $parenthesisOpener + 1 ), ' ' );
					$phpcsFile->fixer->endChangeset();
				}
			}
		}

		if ( isset( $this->tokens[ $parenthesisOpener ]['parenthesis_closer'] ) ) {

			$parenthesisCloser = $this->tokens[ $parenthesisOpener ]['parenthesis_closer'];

			if ( T_CLOSE_PARENTHESIS !== $this->tokens[ ( $parenthesisOpener + 1 ) ]['code'] ) {

				// Checking this: if (...[*]) {}.
				if ( T_WHITESPACE !== $this->tokens[ ( $parenthesisCloser - 1 ) ]['code'] ) {
					$error = 'No space before closing parenthesis is prohibited';
					$fix   = $phpcsFile->addFixableError( $error, $parenthesisCloser, 'NoSpaceBeforeCloseParenthesis' );

					if ( true === $fix ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->addContentBefore( $parenthesisCloser, ' ' );
						$phpcsFile->fixer->endChangeset();
					}
				} elseif ( ' ' !== $this->tokens[ ( $parenthesisCloser - 1 ) ]['content'] ) {
					$prevNonEmpty = $phpcsFile->findPrevious( PHP_CodeSniffer_Tokens::$emptyTokens, ( $parenthesisCloser - 1 ), null, true );
					if ( $this->tokens[ ( $parenthesisCloser ) ]['line'] === $this->tokens[ ( $prevNonEmpty + 1 ) ]['line'] ) {
						$error = 'Expected exactly one space before closing parenthesis; "%s" found.';
						$fix   = $phpcsFile->addFixableError(
							$error,
							$stackPtr,
							'ExtraSpaceBeforeCloseParenthesis',
							$this->tokens[ ( $parenthesisCloser - 1 ) ]['content']
						);

						if ( true === $fix ) {
							$phpcsFile->fixer->beginChangeset();
							$phpcsFile->fixer->replaceToken( ( $parenthesisCloser - 1 ), ' ' );
							$phpcsFile->fixer->endChangeset();
						}
					}
				}

				if (
					T_WHITESPACE !== $this->tokens[ ( $parenthesisCloser + 1 ) ]['code']
					&& ( isset( $scopeOpener ) && T_COLON !== $this->tokens[ $scopeOpener ]['code'] )
				) {
					$error = 'Space between opening control structure and closing parenthesis is required';
					$fix   = $phpcsFile->addFixableError( $error, $scopeOpener, 'NoSpaceAfterCloseParenthesis' );

					if ( true === $fix ) {
						$phpcsFile->fixer->beginChangeset();
						$phpcsFile->fixer->addContentBefore( $scopeOpener, ' ' );
						$phpcsFile->fixer->endChangeset();
					}
				}
			} // End if().

			if ( isset( $this->tokens[ $parenthesisOpener ]['parenthesis_owner'] )
				&& ( isset( $scopeOpener )
				&& $this->tokens[ $parenthesisCloser ]['line'] !== $this->tokens[ $scopeOpener ]['line'] )
			) {
				$error = 'Opening brace should be on the same line as the declaration';
				$fix   = $phpcsFile->addFixableError( $error, $parenthesisOpener, 'OpenBraceNotSameLine' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();

					for ( $i = ( $parenthesisCloser + 1 ); $i < $scopeOpener; $i++ ) {
						$phpcsFile->fixer->replaceToken( $i, '' );
					}

					$phpcsFile->fixer->addContent( $parenthesisCloser, ' ' );
					$phpcsFile->fixer->endChangeset();
				}
				return;

			} elseif (
				T_WHITESPACE === $this->tokens[ ( $parenthesisCloser + 1 ) ]['code']
				&& ' ' !== $this->tokens[ ( $parenthesisCloser + 1 ) ]['content']
			) {

				// Checking this: if (...) [*]{}.
				$error = 'Expected exactly one space between closing parenthesis and opening control structure; "%s" found.';
				$fix   = $phpcsFile->addFixableError(
					$error,
					$stackPtr,
					'ExtraSpaceAfterCloseParenthesis',
					$this->tokens[ ( $parenthesisCloser + 1 ) ]['content']
				);

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->replaceToken( ( $parenthesisCloser + 1 ), ' ' );
					$phpcsFile->fixer->endChangeset();
				}
			} // End if().
		} // End if().

		if ( true === $this->blank_line_check && isset( $scopeOpener ) ) {
			$firstContent = $phpcsFile->findNext( T_WHITESPACE, ( $scopeOpener + 1 ), null, true );

			// We ignore spacing for some structures that tend to have their own rules.
			$ignore = array(
				T_FUNCTION             => true,
				T_CLOSURE              => true,
				T_CLASS                => true,
				T_ANON_CLASS           => true,
				T_INTERFACE            => true,
				T_TRAIT                => true,
				T_DOC_COMMENT_OPEN_TAG => true,
				T_CLOSE_TAG            => true,
				T_COMMENT              => true,
			);

			if ( ! isset( $ignore[ $this->tokens[ $firstContent ]['code'] ] )
				&& $this->tokens[ $firstContent ]['line'] > ( $this->tokens[ $scopeOpener ]['line'] + 1 )
			) {
				$error = 'Blank line found at start of control structure';
				$fix   = $phpcsFile->addFixableError( $error, $scopeOpener, 'BlankLineAfterStart' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();

					for ( $i = ( $scopeOpener + 1 ); $i < $firstContent; $i++ ) {
						$phpcsFile->fixer->replaceToken( $i, '' );
					}

					$phpcsFile->fixer->addNewline( $scopeOpener );
					$phpcsFile->fixer->endChangeset();
				}
			}

			if ( $firstContent !== $scopeCloser ) {
				$lastContent = $phpcsFile->findPrevious( T_WHITESPACE, ( $scopeCloser - 1 ), null, true );

				$lastNonEmptyContent = $phpcsFile->findPrevious( PHP_CodeSniffer_Tokens::$emptyTokens, ( $scopeCloser - 1 ), null, true );

				$checkToken = $lastContent;
				if ( isset( $this->tokens[ $lastNonEmptyContent ]['scope_condition'] ) ) {
					$checkToken = $this->tokens[ $lastNonEmptyContent ]['scope_condition'];
				}

				if ( ! isset( $ignore[ $this->tokens[ $checkToken ]['code'] ] )
					&& $this->tokens[ $lastContent ]['line'] <= ( $this->tokens[ $scopeCloser ]['line'] - 2 )
				) {
					for ( $i = ( $scopeCloser - 1 ); $i > $lastContent; $i-- ) {
						if ( $this->tokens[ $i ]['line'] < $this->tokens[ $scopeCloser ]['line']
							&& T_OPEN_TAG !== $this->tokens[ $firstContent ]['code']
						) {
							// TODO: Reporting error at empty line won't highlight it in IDE.
							$error = 'Blank line found at end of control structure';
							$fix   = $phpcsFile->addFixableError( $error, $i, 'BlankLineBeforeEnd' );

							if ( true === $fix ) {
								$phpcsFile->fixer->beginChangeset();

								for ( $j = ( $lastContent + 1 ); $j < $scopeCloser; $j++ ) {
									$phpcsFile->fixer->replaceToken( $j, '' );
								}

								$phpcsFile->fixer->addNewlineBefore( $scopeCloser );
								$phpcsFile->fixer->endChangeset();
							}
							break;
						} // End if().
					} // End for().
				} // End if().
			} // End if().
			unset( $ignore );
		} // End if().

		if ( ! isset( $scopeCloser ) || false === $this->blank_line_after_check ) {
			return;
		}

		$trailingContent = $phpcsFile->findNext( PHP_CodeSniffer_Tokens::$emptyTokens, ( $scopeCloser + 1 ), null, true );
		if ( false === $trailingContent ) {
			return;
		}

		if ( T_ELSE === $this->tokens[ $trailingContent ]['code'] && T_IF === $this->tokens[ $stackPtr ]['code'] ) {
			// IF with ELSE.
			return;
		}

		if ( T_BREAK === $this->tokens[ $trailingContent ]['code'] ) {
			// If this BREAK is closing a CASE, we don't need the
			// blank line after this control structure.
			if ( isset( $this->tokens[ $trailingContent ]['scope_condition'] ) ) {
				$condition = $this->tokens[ $trailingContent ]['scope_condition'];
				if ( T_CASE === $this->tokens[ $condition ]['code'] || T_DEFAULT === $this->tokens[ $condition ]['code'] ) {
					return;
				}
			}
		}

		if ( T_CLOSE_TAG === $this->tokens[ $trailingContent ]['code'] ) {
			// At the end of the script or embedded code.
			return;
		}

		if ( T_CLOSE_CURLY_BRACKET === $this->tokens[ $trailingContent ]['code'] ) {
			// Another control structure's closing brace.
			if ( isset( $this->tokens[ $trailingContent ]['scope_condition'] ) ) {
				$owner = $this->tokens[ $trailingContent ]['scope_condition'];
				if ( in_array( $this->tokens[ $owner ]['code'], array( T_FUNCTION, T_CLOSURE, T_CLASS, T_ANON_CLASS, T_INTERFACE, T_TRAIT ), true ) ) {
					// The next content is the closing brace of a function, class, interface or trait
					// so normal function/class rules apply and we can ignore it.
					return;
				}
			}

			if ( ( $this->tokens[ $scopeCloser ]['line'] + 1 ) !== $this->tokens[ $trailingContent ]['line'] ) {
				// TODO: Won't cover following case: "} echo 'OK';".
				$error = 'Blank line found after control structure';
				$fix   = $phpcsFile->addFixableError( $error, $scopeCloser, 'BlankLineAfterEnd' );

				if ( true === $fix ) {
					$phpcsFile->fixer->beginChangeset();

					for ( $i = ( $scopeCloser + 1 ); $i < $trailingContent; $i++ ) {
						$phpcsFile->fixer->replaceToken( $i, '' );
					}

					// TODO: Instead a separate error should be triggered when content comes right after closing brace.
					$phpcsFile->fixer->addNewlineBefore( $trailingContent );
					$phpcsFile->fixer->endChangeset();
				}
			}
		} // End if().

	} // End process().

} // End class.
