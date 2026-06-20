import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
	static targets = ['dialog'];


	initialize() {
		window.addEventListener('modal:close', () => this.dialogTarget.close());
		window.addEventListener('modal:open', () => this.dialogTarget.showModal());
	}

	toggle()
	{
		if (!this.dialogTarget.open) {
			this.dialogTarget.showModal();
		} else {
			this.dialogTarget.close();
		}
	}
}
