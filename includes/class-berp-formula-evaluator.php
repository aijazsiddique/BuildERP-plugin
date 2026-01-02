<?php
/**
 * Salary formula evaluator.
 *
 * Safe parser for salary formulas used by the formula builder and payroll.
 *
 * @package BuildErp
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Salary formula evaluator.
 */
class BERP_Formula_Evaluator {

	/**
	 * Supported functions and their argument counts.
	 *
	 * @var array
	 */
	protected $functions = array(
		'if'    => 3,
		'min'   => 2,
		'max'   => 2,
		'round' => 2,
		'abs'   => 1,
	);

	/**
	 * Operator precedence (higher = stronger).
	 *
	 * @var array
	 */
	protected $precedence = array(
		'||' => 1,
		'&&' => 2,
		'==' => 3,
		'!=' => 3,
		'>=' => 4,
		'<=' => 4,
		'>'  => 4,
		'<'  => 4,
		'+'  => 5,
		'-'  => 5,
		'*'  => 6,
		'/'  => 6,
		'%'  => 6,
		'^'  => 7,
	);

	/**
	 * Operators that are left associative.
	 *
	 * @var array
	 */
	protected $left_associative = array(
		'||',
		'&&',
		'==',
		'!=',
		'>=',
		'<=',
		'>',
		'<',
		'+',
		'-',
		'*',
		'/',
		'%',
		'^',
	);

	/**
	 * Evaluate a formula against provided variables.
	 *
	 * @param string $formula   Raw formula.
	 * @param array  $variables Associative array of variable => value.
	 *
	 * @return float|WP_Error
	 */
	public function evaluate( $formula, $variables = array() ) {
		$tokens = $this->tokenize( $formula );
		if ( is_wp_error( $tokens ) ) {
			return $tokens;
		}

		$rpn = $this->to_rpn( $tokens );
		if ( is_wp_error( $rpn ) ) {
			return $rpn;
		}

		return $this->evaluate_rpn( $rpn, $variables );
	}

	/**
	 * Tokenize the formula string.
	 *
	 * @param string $formula Formula string.
	 * @return array|WP_Error
	 */
	protected function tokenize( $formula ) {
		$formula = trim( $formula );
		if ( '' === $formula ) {
			return new WP_Error( 'berp_formula_empty', __( 'Formula is empty.', 'builderp' ) );
		}

		$pattern = '/(\\d*\\.\\d+|\\d+)|([A-Za-z_][A-Za-z0-9_]*)|(>=|<=|==|!=|&&|\\|\\||[+\\-*\\/^%(),<>])/';
		preg_match_all( $pattern, $formula, $matches );

		$tokens = array();
		$length = 0;
		foreach ( $matches[0] as $token ) {
			$tokens[] = $token;
			$length  += strlen( $token );
		}

		// If parsed length does not match, invalid characters exist.
		if ( strlen( preg_replace( '/\\s+/', '', $formula ) ) !== $length ) {
			return new WP_Error( 'berp_formula_invalid', __( 'Formula contains invalid characters.', 'builderp' ) );
		}

		return $tokens;
	}

	/**
	 * Convert tokens to Reverse Polish Notation via shunting-yard.
	 *
	 * @param array $tokens Tokens.
	 * @return array|WP_Error
	 */
	protected function to_rpn( $tokens ) {
		$output      = array();
		$stack       = array();
		$arg_counts  = array();
		$previous    = null;
		$token_count = count( $tokens );

		for ( $i = 0; $i < $token_count; $i++ ) {
			$token = $tokens[ $i ];

			// Check for functions FIRST (before variables, as function names match variable pattern).
			$lower = strtolower( $token );
			if ( isset( $this->functions[ $lower ] ) ) {
				$stack[]      = $lower;
				$arg_counts[] = 1;
				$previous     = $lower;
				continue;
			}

			if ( is_numeric( $token ) || $this->is_variable( $token ) ) {
				$output[] = $token;
				$previous = $token;
				continue;
			}

			if ( ',' === $token ) {
				while ( ! empty( $stack ) && '(' !== end( $stack ) ) {
					$output[] = array_pop( $stack );
				}
				if ( empty( $arg_counts ) ) {
					return new WP_Error( 'berp_formula_comma', __( 'Unexpected comma or argument separator.', 'builderp' ) );
				}
				$last_index = count( $arg_counts ) - 1;
				++$arg_counts[ $last_index ];
				$previous = $token;
				continue;
			}

			if ( '(' === $token ) {
				$stack[]  = $token;
				$previous = $token;
				continue;
			}

			if ( ')' === $token ) {
				while ( ! empty( $stack ) && '(' !== end( $stack ) ) {
					$output[] = array_pop( $stack );
				}
				if ( empty( $stack ) ) {
					return new WP_Error( 'berp_formula_parentheses', __( 'Mismatched parentheses detected.', 'builderp' ) );
				}
				array_pop( $stack );

				if ( ! empty( $stack ) && isset( $this->functions[ end( $stack ) ] ) ) {
					$func         = array_pop( $stack );
					$func_arg_cnt = array_pop( $arg_counts );
					$output[]     = array(
						'func' => $func,
						'argc' => $func_arg_cnt,
					);
				}
				$previous = $token;
				continue;
			}

			// Operator handling.
			$op = $token;

			// Unary minus handling.
			if ( '-' === $op && ( null === $previous || in_array( $previous, array( '(', ',', '+', '-', '*', '/', '^', '%', '&&', '||', '==', '!=', '>=', '<=', '>', '<' ), true ) ) ) {
				$op                           = 'u-';
				$this->precedence['u-']       = 8;
				$this->left_associative['u-'] = false;
			}

			if ( ! isset( $this->precedence[ $op ] ) && 'u-' !== $op ) {
				return new WP_Error( 'berp_formula_operator', __( 'Unsupported operator found in formula.', 'builderp' ) );
			}

			while ( ! empty( $stack ) && $this->is_operator( end( $stack ) ) ) {
				$top = end( $stack );
				if ( ( $this->is_left_associative( $op ) && $this->precedence[ $op ] <= $this->precedence[ $top ] )
					|| ( ! $this->is_left_associative( $op ) && $this->precedence[ $op ] < $this->precedence[ $top ] )
				) {
					$output[] = array_pop( $stack );
				} else {
					break;
				}
			}

			$stack[]  = $op;
			$previous = $op;
		}

		while ( ! empty( $stack ) ) {
			$top = array_pop( $stack );
			if ( '(' === $top || ')' === $top ) {
				return new WP_Error( 'berp_formula_parentheses_end', __( 'Mismatched parentheses detected.', 'builderp' ) );
			}
			$output[] = $top;
		}

		return $output;
	}

	/**
	 * Evaluate Reverse Polish Notation stack.
	 *
	 * @param array $rpn       RPN tokens.
	 * @param array $variables Provided variables.
	 * @return float|WP_Error
	 */
	protected function evaluate_rpn( $rpn, $variables ) {
		$stack = array();

		foreach ( $rpn as $token ) {
			// Function token.
			if ( is_array( $token ) && isset( $token['func'] ) ) {
				$func = $token['func'];
				$argc = $token['argc'];
				$args = array();

				if ( count( $stack ) < $argc ) {
					return new WP_Error( 'berp_formula_args', __( 'Not enough arguments for function.', 'builderp' ) );
				}

				for ( $i = 0; $i < $argc; $i++ ) {
					array_unshift( $args, array_pop( $stack ) );
				}

				$result = $this->apply_function( $func, $args );
				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$stack[] = $result;
				continue;
			}

			if ( $this->is_operator( $token ) ) {
				if ( 'u-' === $token ) {
					if ( empty( $stack ) ) {
						return new WP_Error( 'berp_formula_unary', __( 'Invalid unary operator usage.', 'builderp' ) );
					}
					$value   = array_pop( $stack );
					$stack[] = -1 * $value;
					continue;
				}

				if ( count( $stack ) < 2 ) {
					return new WP_Error( 'berp_formula_stack', __( 'Formula stack is invalid.', 'builderp' ) );
				}

				$b = array_pop( $stack );
				$a = array_pop( $stack );
				$r = $this->apply_operator( $token, $a, $b );
				if ( is_wp_error( $r ) ) {
					return $r;
				}
				$stack[] = $r;
				continue;
			}

			if ( is_numeric( $token ) ) {
				$stack[] = (float) $token;
				continue;
			}

			$var_key = strtolower( $token );
			if ( ! array_key_exists( $var_key, $variables ) ) {
				return new WP_Error(
					'berp_formula_missing_variable',
					sprintf(
						/* translators: %s variable name */
						__( 'Variable "%s" is not provided.', 'builderp' ),
						$var_key
					)
				);
			}

			$stack[] = (float) $variables[ $var_key ];
		}

		if ( 1 !== count( $stack ) ) {
			return new WP_Error( 'berp_formula_result', __( 'Formula could not be resolved.', 'builderp' ) );
		}

		$final_result = (float) array_pop( $stack );

		// Final safety: Ensure result is finite.
		if ( ! is_finite( $final_result ) ) {
			return new WP_Error(
				'berp_formula_invalid_result',
				__( 'Formula resulted in invalid value (Infinity or NaN). This usually indicates division by zero.', 'builderp' )
			);
		}

		return $final_result;
	}

	/**
	 * Apply arithmetic or logical operator.
	 *
	 * @param string $operator Operator.
	 * @param float  $a        Left.
	 * @param float  $b        Right.
	 * @return float|WP_Error
	 */
	protected function apply_operator( $operator, $a, $b ) {
		switch ( $operator ) {
			case '+':
				return $a + $b;
			case '-':
				return $a - $b;
			case '*':
				return $a * $b;
			case '/':
				if ( 0.0 === (float) $b ) {
					return new WP_Error( 'berp_formula_division', __( 'Division by zero detected.', 'builderp' ) );
				}
				$result = $a / $b;

				// Additional safety: Check for Infinity or NaN.
				if ( ! is_finite( $result ) ) {
					return new WP_Error( 'berp_formula_division', __( 'Division by zero detected.', 'builderp' ) );
				}

				return $result;
			case '%':
				if ( 0.0 === (float) $b ) {
					return new WP_Error( 'berp_formula_modulo', __( 'Modulo by zero detected.', 'builderp' ) );
				}
				$result = fmod( $a, $b );

				// Additional safety: Check for Infinity or NaN.
				if ( ! is_finite( $result ) ) {
					return new WP_Error( 'berp_formula_modulo', __( 'Modulo by zero detected.', 'builderp' ) );
				}

				return $result;
			case '^':
				return pow( $a, $b );
			case '==':
				return (float) ( $a == $b ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			case '!=':
				return (float) ( $a != $b ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			case '>':
				return (float) ( $a > $b );
			case '<':
				return (float) ( $a < $b );
			case '>=':
				return (float) ( $a >= $b );
			case '<=':
				return (float) ( $a <= $b );
			case '&&':
				return (float) ( $a && $b );
			case '||':
				return (float) ( $a || $b );
		}

		return new WP_Error( 'berp_formula_operator_unknown', __( 'Unknown operator encountered.', 'builderp' ) );
	}

	/**
	 * Apply supported function.
	 *
	 * @param string $func Function name.
	 * @param array  $args Arguments.
	 * @return float|WP_Error
	 */
	protected function apply_function( $func, $args ) {
		$func = strtolower( $func );

		if ( ! isset( $this->functions[ $func ] ) ) {
			return new WP_Error( 'berp_formula_func', __( 'Unsupported function in formula.', 'builderp' ) );
		}

		if ( count( $args ) !== (int) $this->functions[ $func ] ) {
			return new WP_Error( 'berp_formula_func_args', __( 'Incorrect number of arguments for function.', 'builderp' ) );
		}

		switch ( $func ) {
			case 'if':
				return ( (float) $args[0] ) ? (float) $args[1] : (float) $args[2];
			case 'min':
				return min( (float) $args[0], (float) $args[1] );
			case 'max':
				return max( (float) $args[0], (float) $args[1] );
			case 'round':
				return round( (float) $args[0], (int) $args[1] );
			case 'abs':
				return abs( (float) $args[0] );
		}

		return new WP_Error( 'berp_formula_func_unknown', __( 'Unknown function encountered.', 'builderp' ) );
	}

	/**
	 * Check if token is a variable.
	 *
	 * @param string $token Token.
	 * @return bool
	 */
	protected function is_variable( $token ) {
		return (bool) preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $token );
	}

	/**
	 * Check if token is an operator.
	 *
	 * @param string $token Token.
	 * @return bool
	 */
	protected function is_operator( $token ) {
		return isset( $this->precedence[ $token ] );
	}

	/**
	 * Check if operator is left associative.
	 *
	 * @param string $op Operator.
	 * @return bool
	 */
	protected function is_left_associative( $op ) {
		return in_array( $op, $this->left_associative, true );
	}
}

