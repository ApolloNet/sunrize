<?php
/**
 * Sunrize bot.
 *
 * Tic tac toe bot for https://botsarena.tinad.fr/tictactoe
 *
 * TODO: refactor the indentation madness.
 */

/**
 * Access Control.
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

/**
 * Get request.
 */
function get_request() {
  return json_decode(file_get_contents('php://input'), TRUE);
}

/**
 * Init the Sunrize bot.
 */
function init() {
  $response = array(
    'name' => 'Sunrize',
  );
  return $response;
}

/**
 * Get board.
 */
function get_board() {
  $request = get_request();
  $board = $request['board'];
  return $board;
}

/**
 * Get player symbol.
 * @param string $who Player 'me' or 'him'.
 */
function get_player_symbol($who) {
  $request = get_request();
  $player_index = $request['player-index'];
  $symbols = array(
    0 => 'X',
    1 => 'O',
  );
  if ($who == 'him') {
    $player_index = ($player_index == 0) ? 1 : 0;
  }
  $symbol = $symbols[$player_index];
  return $symbol;
}

/**
 * Get cells.
 * @param string $who Player 'me', 'him' or '' for empty cells.
 */
function get_cells($who) {
  $board = get_board();
  $symbol = '';
  if ($who != '') {
    $symbol = get_player_symbol($who);
  }
  $cells = array_keys($board, $symbol);
  return $cells;
}

/**
 * Get cell score.
 * string $cell Cell as '1-2'.
 */
function get_cell_score($cell) {
  $board = get_board();
  return $board[$cell];
}

/**
 * Get line scores.
 * @param array $line Line.
 */
function get_line_scores($line) {
  $line_scores = array();
  foreach ($line as $cell) {
    $line_scores[$cell] = get_cell_score($cell);
  }
  return $line_scores;
}

/**
 * Get lines.
 */
function get_lines() {
  $lines = array(
    // horiz.
    array('0-0', '0-1', '0-2'),
    array('1-0', '1-1', '1-2'),
    array('2-0', '2-1', '2-2'),
    // vert.
    array('0-0', '1-0', '2-0'),
    array('0-1', '1-1', '2-1'),
    array('0-2', '1-2', '2-2'),
    // diag.
    array('0-0', '1-1', '2-2'),
    array('2-0', '1-1', '0-2'),
  );
  return $lines;
}

/**
 * I have 2 cells on the same line, here comes the third.
 */
function play_win() {
  $play = false;
  $my_symbol = get_player_symbol('me');
  $lines = get_lines();

  foreach ($lines as $line) {

    $line_scores = get_line_scores($line);
    $line_scores_count = array_count_values($line_scores);

    if ($line_scores_count[$my_symbol] == 2 && $line_scores_count[''] == 1) {
      foreach ($line_scores as $cell => $score) {
        if ($score === '') {
          $play = $cell;
        }
      }
    }

    if ($play != false) {
      break;
    }
  }

  return $play;
}

/**
 * Opponent has 2 cells on the same line, I break the pattern.
 */
function play_no_lose() {
  $play = false;
  $his_symbol = get_player_symbol('him');
  $lines = get_lines();

  foreach ($lines as $line) {

    $line_scores = get_line_scores($line);
    $line_scores_count = array_count_values($line_scores);

    if ($line_scores_count[$his_symbol] == 2 && $line_scores_count[''] == 1) {
      foreach ($line_scores as $cell => $score) {
        if ($score === '') {
          $play = $cell;
        }
      }
    }

    if ($play != false) {
      break;
    }
  }

  return $play;
}

/**
 * Play center.
 */
function play_center() {
  $play = false;
  $center_score = get_cell_score('1-1');
  if ($center_score == '') {
    $play = '1-1';
  }
  return $play;
}

/**
 * PLay a second symbol on a line where I already have a symbol.
 */
function play_second() {
  $play = false;
  $my_symbol = get_player_symbol('him');
  $lines = get_lines();

  foreach ($lines as $line) {

    $line_scores = get_line_scores($line);
    $line_scores_count = array_count_values($line_scores);

    if ($line_scores_count[$my_symbol] == 1 && $line_scores_count[''] == 2) {
      foreach ($line_scores as $cell => $score) {
        // TODO : randomize between the 2 ?
        if ($score === '') {
          $play = $cell;
        }
      }
    }

    if ($play != false) {
      break;
    }
  }

  return $play;
}

/**
 * Play random.
 */
function play_random() {
  $empty_cells = get_cells('');
  $nb_empty_cells = count($empty_cells);
  $key = ($nb_empty_cells == 1) ? 0 : rand(0, $nb_empty_cells - 1);
  return $empty_cells[$key];
}

/**
 * PLay.
 */
function play() {

  // Hits.
  $hits = array(
    'play_win' => play_win(),
    'play_no_lose' => play_no_lose(),
    'play_center' => play_center(),
    'play_second' => play_second(),
    'play_random' => play_random(),
  );

  //TODO with array_walk_recursive() ?
  foreach ($hits as $k => $hit) {
    $play = $hit;
    if ($play != false) {
      $method = $k;
      break;
    }
  }

  $response['method'] = $method;
  $response['play'] = $play;
  return $response;
}

/**
 * Send response.
 */
function send_response() {
  $request = get_request();

  if ($request['action'] == 'init') {
    $response = init();
  }

  if ($request['action'] == 'play-turn') {
    $response = play();
  }

  return json_encode($response);
}

/**
 * Proceed.
 */
echo send_response();
