<?php
/**
 * Repair: scope attributes on table headers.
 *
 * @package A11yFix
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * th without scope is ambiguous for screen readers in two-direction tables.
 * Conservative: scope="col" only for th in thead or the first row;
 * scope="row" only for a th that is the first cell of its row.
 */
class A11yFix_Repair_Table_Scope implements A11yFix_Repair {

	/**
	 * Id.
	 *
	 * @return string
	 */
	public function id() {
		return 'table_scope';
	}

	/**
	 * Label.
	 *
	 * @return string
	 */
	public function label() {
		return 'Add scope to table headers';
	}

	/**
	 * Risky?
	 *
	 * @return bool
	 */
	public function risky() {
		return false;
	}

	/**
	 * Description.
	 *
	 * @return string
	 */
	public function description() {
		return 'Adds scope="col"/"row" to <th> cells that have none, so data cells are announced with the right header.';
	}

	/**
	 * Apply.
	 *
	 * @param DOMDocument $dom Document.
	 * @param array       $ctx Unused.
	 * @return A11yFix_Change[]
	 */
	public function apply( DOMDocument $dom, array $ctx = array() ) {
		$changes = array();

		foreach ( $dom->getElementsByTagName( 'table' ) as $table ) {
			if ( ! $table instanceof DOMElement ) {
				continue;
			}

			// Column headers: thead th, or th in the first row.
			$col_ths = array();
			$heads   = $table->getElementsByTagName( 'thead' );
			if ( $heads->length > 0 ) {
				foreach ( $heads->item( 0 )->getElementsByTagName( 'th' ) as $th ) {
					$col_ths[] = $th;
				}
			}
			$rows = $this->rows_of( $table );
			if ( $rows ) {
				foreach ( $rows[0] as $cell ) {
					if ( 'th' === strtolower( $cell->tagName ) ) {
						$col_ths[] = $cell;
					}
				}
			}
			foreach ( $col_ths as $th ) {
				$this->add_scope( $th, 'col', $changes );
			}

			// Row headers: th as the first cell of any row after the first.
			foreach ( array_slice( $rows, 1 ) as $cells ) {
				if ( $cells && 'th' === strtolower( $cells[0]->tagName ) ) {
					$this->add_scope( $cells[0], 'row', $changes );
				}
			}
		}

		return $changes;
	}

	/**
	 * Rows of a table as arrays of direct child cells.
	 *
	 * @param DOMElement $table Table.
	 * @return DOMElement[][]
	 */
	private function rows_of( DOMElement $table ) {
		$rows = array();
		foreach ( $table->getElementsByTagName( 'tr' ) as $tr ) {
			if ( ! $tr instanceof DOMElement ) {
				continue;
			}
			$cells = array();
			foreach ( $tr->childNodes as $cell ) {
				if ( $cell instanceof DOMElement && in_array( strtolower( $cell->tagName ), array( 'td', 'th' ), true ) ) {
					$cells[] = $cell;
				}
			}
			$rows[] = $cells;
		}

		return $rows;
	}

	/**
	 * Add scope when missing and record the change.
	 *
	 * @param DOMElement $th      Header cell.
	 * @param string     $scope   col|row.
	 * @param array      $changes Change collector.
	 * @return void
	 */
	private function add_scope( DOMElement $th, $scope, array &$changes ) {
		if ( '' !== trim( $th->getAttribute( 'scope' ) ) ) {
			return;
		}
		$th->setAttribute( 'scope', $scope );
		$changes[] = new A11yFix_Change(
			array(
				'repair' => 'table_scope',
				'target' => 'th',
				'attr'   => 'scope',
				'before' => '',
				'after'  => $scope,
			)
		);
	}
}
