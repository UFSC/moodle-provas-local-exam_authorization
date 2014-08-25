<?php

// Este bloco é parte do Moodle Provas - http://tutoriais.moodle.ufsc.br/provas/
//
// O Moodle Provas pode ser utilizado livremente por instituições integradas à
// UAB - Universidade Aberta do Brasil (http://www.uab.capes.gov.br/), assim como ser
// modificado para adequação à estrutura destas instituições
// sobre os termos da "GNU General Public License" como publicada pela
// "Free Software Foundation".

// You should have received a copy of the GNU General Public License
// along with this plugin.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A customized error page.
 *
 * @package    local_exam_authorization
 * @copyright  2014 onwards Antonio Carlos Mariani (https://moodle.ufsc.br)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include('../../config.php');

$errorcode = optional_param('errorcode', '', PARAM_TEXT);
if(empty($errorcode)) {
    require_logout();
    redirect(new moodle_url('/'));
} else {
    $url = new moodle_url('/local/exam_authorization/print_error.php');
    print_error($errorcode, 'local_exam_authorization', $url);
}
