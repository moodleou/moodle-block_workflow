// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

import ModalFactory from 'core/modal_factory';

/**
 * User info functionality for a popup in workflow block.
 *
 * @module block_workflow/userinfo
 * @copyright 2023 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Popup {
    CSS = {
        USERINFOCLASS: '.userinfoclass'
    };

    PARAMS = {
        id: 'id',
        HEADER: 'header',
        BODY: 'body',
        STEPNO: 'stepno'
    };

    /**
     * Initial function.
     */
    initializer() {
        document.querySelectorAll(this.CSS.USERINFOCLASS).forEach(node => {
            const header = node.getAttribute(this.PARAMS.HEADER);
            const body = node.getAttribute(this.PARAMS.BODY);
            node.onclick = event => this.displayDialog(event, header, body);
        });
    }

    /**
     * Handle display modal.
     *
     * @param {Object} e The event object.
     * @param {String} header Title string.
     * @param {String} body Body data.
     */
    async displayDialog(e, header, body) {
        e.preventDefault();
        const modal = await ModalFactory.create({
            title: header,
            body: body,
            large: true,
        });
        modal.attachToDOM();
        modal.getRoot()[0].querySelector('.modal-dialog').style.width = 'fit-content';
        modal.show();
    }
}

/**
 * Handle userinfo action.
 */
export const init = () => {
    const popup = new Popup();
    popup.initializer();
};
