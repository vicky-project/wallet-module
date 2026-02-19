class CategoryIconPicker {
	constructor() {
		this.iconGrid = document.getElementById("iconGrid");
		this.selectedIconPreview = document.getElementById("selectedIconPreview");
		this.iconInput = document.getElementById("icon");
		this.iconSearch = document.getElementById("iconSearch");
		this.dropdown = null;
		this.currentCategory = "all";

		this.init();
	}

	init() {
		if (this.iconGrid) {
			this.initializeDropdown();
			this.renderIcons(this.getFinanceIcons());
			this.setupEventListeners();
			this.setupCategoryFilters();
		}
	}

	initializeDropdown() {
		const button = document.getElementById("iconPickerButton");
		if (button) {
			this.dropdown = new bootstrap.Dropdown(button);
		}
	}

	getFinanceIcons() {
		return [
			// 🏦 BANK & UANG
			{
				name: "bi-bank",
				category: "bank",
				tags: ["bank", "perbankan", "keuangan"]
			},
			{
				name: "bi-cash-stack",
				category: "bank",
				tags: ["uang", "cash", "tunai"]
			},
			{ name: "bi-cash-coin", category: "bank", tags: ["koin", "uang koin"] },
			{ name: "bi-coin", category: "bank", tags: ["koin", "mata uang"] },
			{
				name: "bi-currency-exchange",
				category: "bank",
				tags: ["tukar", "valas", "mata uang"]
			},
			{
				name: "bi-safe",
				category: "bank",
				tags: ["brankas", "aman", "simpan"]
			},

			// 💰 PEMASUKAN & PENGHASILAN
			{
				name: "bi-wallet",
				category: "income",
				tags: ["dompet", "uang", "saldo"]
			},
			{ name: "bi-wallet2", category: "income", tags: ["dompet", "keuangan"] },
			{
				name: "bi-graph-up",
				category: "income",
				tags: ["naik", "profit", "keuntungan"]
			},
			{
				name: "bi-graph-up-arrow",
				category: "income",
				tags: ["grafik naik", "investasi"]
			},
			{
				name: "bi-arrow-up-circle",
				category: "income",
				tags: ["pemasukan", "tambah"]
			},
			{
				name: "bi-arrow-up-right-circle",
				category: "income",
				tags: ["naik", "profit"]
			},
			{
				name: "bi-briefcase",
				category: "income",
				tags: ["kerja", "profesi", "karir"]
			},
			{
				name: "bi-laptop",
				category: "income",
				tags: ["freelance", "kerja online"]
			},
			{ name: "bi-phone", category: "income", tags: ["bisnis", "telepon"] },
			{
				name: "bi-gift",
				category: "income",
				tags: ["hadiah", "bonus", "hibah"]
			},
			{
				name: "bi-trophy",
				category: "income",
				tags: ["bonus", "prestasi", "reward"]
			},

			// 💸 PENGELUARAN & BELANJA
			{
				name: "bi-cart",
				category: "expense",
				tags: ["belanja", "market", "toko"]
			},
			{
				name: "bi-cart-check",
				category: "expense",
				tags: ["belanja", "checkout"]
			},
			{
				name: "bi-cart-x",
				category: "expense",
				tags: ["belanja batal", "return"]
			},
			{
				name: "bi-bag",
				category: "expense",
				tags: ["tas belanja", "shopping"]
			},
			{ name: "bi-bag-check", category: "expense", tags: ["belanja selesai"] },
			{
				name: "bi-basket",
				category: "expense",
				tags: ["keranjang", "belanja"]
			},
			{ name: "bi-basket2", category: "expense", tags: ["keranjang belanja"] },
			{
				name: "bi-arrow-down-circle",
				category: "expense",
				tags: ["pengeluaran", "keluar"]
			},
			{
				name: "bi-arrow-down-right-circle",
				category: "expense",
				tags: ["pengeluaran"]
			},

			// 🏠 KEBUTUHAN RUMAH TANGGA
			{
				name: "bi-house",
				category: "household",
				tags: ["rumah", "tempat tinggal"]
			},
			{
				name: "bi-house-door",
				category: "household",
				tags: ["rumah", "pintu"]
			},
			{
				name: "bi-house-check",
				category: "household",
				tags: ["rumah", "verified"]
			},
			{
				name: "bi-lightning-charge",
				category: "household",
				tags: ["listrik", "energi"]
			},
			{ name: "bi-droplet", category: "household", tags: ["air", "pdam"] },
			{ name: "bi-fire", category: "household", tags: ["gas", "kompor"] },
			{ name: "bi-wifi", category: "household", tags: ["internet", "wifi"] },
			{
				name: "bi-tv",
				category: "household",
				tags: ["televisi", "tv", "hiburan"]
			},

			// 🚗 TRANSPORTASI
			{
				name: "bi-car-front",
				category: "transport",
				tags: ["mobil", "transportasi"]
			},
			{
				name: "bi-car-front-fill",
				category: "transport",
				tags: ["mobil", "kendaraan"]
			},
			{
				name: "bi-train-front",
				category: "transport",
				tags: ["kereta", "commuter"]
			},
			{
				name: "bi-bus-front",
				category: "transport",
				tags: ["bus", "angkutan"]
			},
			{
				name: "bi-bicycle",
				category: "transport",
				tags: ["sepeda", "olahraga"]
			},
			{ name: "bi-fuel-pump", category: "transport", tags: ["bensin", "spbu"] },
			{
				name: "bi-fuel-pump-diesel",
				category: "transport",
				tags: ["solar", "bahan bakar"]
			},
			{
				name: "bi-speedometer",
				category: "transport",
				tags: ["speed", "perjalanan"]
			},

			// 🍔 MAKANAN & MINUMAN
			{
				name: "bi-egg-fried",
				category: "food",
				tags: ["makanan", "telur", "sarapan"]
			},
			{
				name: "bi-cup-hot",
				category: "food",
				tags: ["minuman", "kopi", "teh"]
			},
			{
				name: "bi-cup-straw",
				category: "food",
				tags: ["minuman", "jus", "softdrink"]
			},
			{ name: "bi-egg", category: "food", tags: ["makanan", "protein"] },
			{ name: "bi-egg-fill", category: "food", tags: ["makanan", "telur"] },
			{ name: "bi-cup", category: "food", tags: ["minuman", "gelas"] },
			{ name: "bi-utensils", category: "food", tags: ["makan", "restoran"] },

			// 🏥 KESEHATAN
			{
				name: "bi-heart-pulse",
				category: "health",
				tags: ["kesehatan", "jantung"]
			},
			{
				name: "bi-hospital",
				category: "health",
				tags: ["rumah sakit", "medis"]
			},
			{ name: "bi-capsule", category: "health", tags: ["obat", "kapsul"] },
			{ name: "bi-capsule-pill", category: "health", tags: ["obat", "pil"] },
			{ name: "bi-prescription", category: "health", tags: ["resep", "obat"] },
			{ name: "bi-prescription2", category: "health", tags: ["resep dokter"] },
			{
				name: "bi-activity",
				category: "health",
				tags: ["aktivitas", "kebugaran"]
			},

			// 📚 PENDIDIKAN
			{ name: "bi-book", category: "education", tags: ["buku", "pendidikan"] },
			{
				name: "bi-book-half",
				category: "education",
				tags: ["buku", "pelajaran"]
			},
			{
				name: "bi-journal",
				category: "education",
				tags: ["jurnal", "catatan"]
			},
			{
				name: "bi-journal-bookmark",
				category: "education",
				tags: ["buku catatan"]
			},
			{ name: "bi-pencil", category: "education", tags: ["tulis", "belajar"] },
			{
				name: "bi-pencil-square",
				category: "education",
				tags: ["edit", "tugas"]
			},
			{
				name: "bi-backpack",
				category: "education",
				tags: ["tas sekolah", "sekolah"]
			},
			{
				name: "bi-mortarboard",
				category: "education",
				tags: ["wisuda", "kuliah"]
			},

			// 🎭 HIBURAN
			{ name: "bi-film", category: "entertainment", tags: ["film", "bioskop"] },
			{
				name: "bi-music-note-beamed",
				category: "entertainment",
				tags: ["musik", "lagu"]
			},
			{
				name: "bi-controller",
				category: "entertainment",
				tags: ["game", "permainan"]
			},
			{ name: "bi-dice-5", category: "entertainment", tags: ["game", "dadu"] },
			{
				name: "bi-camera-reels",
				category: "entertainment",
				tags: ["video", "kamera"]
			},
			{
				name: "bi-mic",
				category: "entertainment",
				tags: ["karaoke", "nyanyi"]
			},

			// 🏋️ KEBUGARAN & HOBI
			{ name: "bi-heart", category: "fitness", tags: ["olahraga", "fitness"] },
			{
				name: "bi-heart-fill",
				category: "fitness",
				tags: ["favorit", "olahraga"]
			},
			{ name: "bi-bicycle", category: "fitness", tags: ["sepeda", "olahraga"] },
			{
				name: "bi-dumbbell",
				category: "fitness",
				tags: ["gym", "angkat beban"]
			},
			{
				name: "bi-person-running",
				category: "fitness",
				tags: ["lari", "jogging"]
			},
			{
				name: "bi-person-walking",
				category: "fitness",
				tags: ["jalan", "olahraga"]
			},

			// 💼 BISNIS & INVESTASI
			{ name: "bi-briefcase", category: "business", tags: ["bisnis", "kerja"] },
			{
				name: "bi-briefcase-fill",
				category: "business",
				tags: ["pekerjaan", "karir"]
			},
			{
				name: "bi-building",
				category: "business",
				tags: ["perusahaan", "kantor"]
			},
			{
				name: "bi-graph-up",
				category: "business",
				tags: ["investasi", "saham"]
			},
			{
				name: "bi-bar-chart",
				category: "business",
				tags: ["grafik", "statistik"]
			},
			{
				name: "bi-pie-chart",
				category: "business",
				tags: ["grafik", "presentase"]
			},
			{
				name: "bi-piggy-bank",
				category: "business",
				tags: ["tabungan", "investasi"]
			},
			{ name: "bi-gem", category: "business", tags: ["investasi", "berharga"] },

			// 🛒 TAG & KATEGORI
			{ name: "bi-tag", category: "tags", tags: ["label", "kategori"] },
			{ name: "bi-tags", category: "tags", tags: ["label", "multiple"] },
			{ name: "bi-tag-fill", category: "tags", tags: ["label", "filled"] },
			{ name: "bi-receipt", category: "tags", tags: ["struk", "nota"] },
			{ name: "bi-receipt-cutoff", category: "tags", tags: ["struk", "bukti"] },
			{
				name: "bi-file-earmark-text",
				category: "tags",
				tags: ["dokumen", "laporan"]
			},
			{ name: "bi-file-text", category: "tags", tags: ["file", "catatan"] },

			// 📅 WAKTU & PERENCANAAN
			{ name: "bi-calendar", category: "time", tags: ["kalender", "jadwal"] },
			{
				name: "bi-calendar-check",
				category: "time",
				tags: ["jadwal", "rencana"]
			},
			{
				name: "bi-calendar-week",
				category: "time",
				tags: ["mingguan", "jadwal"]
			},
			{
				name: "bi-calendar-month",
				category: "time",
				tags: ["bulanan", "rencana"]
			},
			{ name: "bi-clock", category: "time", tags: ["waktu", "jam"] },
			{
				name: "bi-clock-history",
				category: "time",
				tags: ["histori", "waktu"]
			},
			{ name: "bi-alarm", category: "time", tags: ["alarm", "pengingat"] },

			// 🔧 LAIN-LAIN
			{ name: "bi-gear", category: "other", tags: ["pengaturan", "setting"] },
			{ name: "bi-gear-fill", category: "other", tags: ["pengaturan"] },
			{ name: "bi-tools", category: "other", tags: ["perkakas", "alat"] },
			{ name: "bi-wrench", category: "other", tags: ["alat", "repair"] },
			{
				name: "bi-shield-check",
				category: "other",
				tags: ["keamanan", "proteksi"]
			},
			{
				name: "bi-shield-exclamation",
				category: "other",
				tags: ["peringatan", "alert"]
			},
			{
				name: "bi-exclamation-triangle",
				category: "other",
				tags: ["warning", "peringatan"]
			},
			{
				name: "bi-exclamation-circle",
				category: "other",
				tags: ["alert", "peringatan"]
			},
			{
				name: "bi-question-circle",
				category: "other",
				tags: ["bantuan", "help"]
			},
			{
				name: "bi-info-circle",
				category: "other",
				tags: ["informasi", "info"]
			},
			{
				name: "bi-check-circle",
				category: "other",
				tags: ["sukses", "selesai"]
			},
			{ name: "bi-x-circle", category: "other", tags: ["batal", "cancel"] }
		];
	}

	setupCategoryFilters() {
		const categories = [
			{ id: "all", name: "Semua", icon: "bi-grid" },
			{ id: "bank", name: "Bank & Uang", icon: "bi-bank" },
			{ id: "income", name: "Pemasukan", icon: "bi-arrow-up-circle" },
			{ id: "expense", name: "Pengeluaran", icon: "bi-arrow-down-circle" },
			{ id: "household", name: "Rumah Tangga", icon: "bi-house" },
			{ id: "transport", name: "Transportasi", icon: "bi-car-front" },
			{ id: "food", name: "Makanan", icon: "bi-egg-fried" },
			{ id: "health", name: "Kesehatan", icon: "bi-heart-pulse" },
			{ id: "education", name: "Pendidikan", icon: "bi-book" },
			{ id: "entertainment", name: "Hiburan", icon: "bi-film" },
			{ id: "business", name: "Bisnis", icon: "bi-briefcase" },
			{ id: "tags", name: "Label", icon: "bi-tag" },
			{ id: "time", name: "Waktu", icon: "bi-calendar" }
		];

		const container = document.getElementById("iconCategoryFilters");
		if (!container) return;

		categories.forEach(cat => {
			const button = document.createElement("button");
			button.type = "button";
			button.className = `btn btn-outline-secondary btn-sm ${
				this.currentCategory === cat.id ? "active" : ""
			}`;
			button.innerHTML = `<i class="bi ${cat.icon}"></i>`;
			button.setAttribute("data-category", cat.id);
			button.title = cat.name;

			button.addEventListener("click", e => {
				e.preventDefault();
				this.setActiveCategory(cat.id);
			});

			container.appendChild(button);
		});
	}

	setActiveCategory(categoryId) {
		this.currentCategory = categoryId;

		// Update active state
		const buttonFilters = document.querySelectorAll(
			"#iconCategoryFilters button"
		);

		if (buttonFilters) {
			buttonFilters.forEach(btn => {
				btn.classList.toggle(
					"active",
					btn.getAttribute("data-category") === categoryId
				);
			});
		}

		// Filter icons
		this.filterIcons();
	}

	filterIcons() {
		const searchTerm = this.iconSearch
			? this.iconSearch.value.toLowerCase()
			: "";
		const allIcons = this.getFinanceIcons();

		let filteredIcons = allIcons;

		// Filter by category
		if (this.currentCategory !== "all") {
			filteredIcons = filteredIcons.filter(
				icon => icon.category === this.currentCategory
			);
		}

		// Filter by search term
		if (searchTerm) {
			filteredIcons = filteredIcons.filter(
				icon =>
					icon.name.toLowerCase().includes(searchTerm) ||
					icon.tags.some(tag => tag.toLowerCase().includes(searchTerm))
			);
		}

		this.renderIcons(filteredIcons);
	}

	renderIcons(icons) {
		if (!this.iconGrid) return;

		this.iconGrid.innerHTML = "";

		if (icons.length === 0) {
			this.iconGrid.innerHTML = `
                <div class="col-12 text-center py-4">
                    <i class="bi bi-search display-4 text-muted mb-3"></i>
                    <p class="text-muted">Tidak ditemukan ikon yang sesuai</p>
                </div>
            `;
			return;
		}

		icons.forEach(icon => {
			const col = document.createElement("div");
			col.className = "col-2 mb-3 text-center";
			col.title = icon.name.replace("bi-", "") + " - " + icon.tags.join(", ");
			col.style.cursor = "pointer";

			const iconElement = document.createElement("i");
			iconElement.className = `${icon.name} fs-4`;
			iconElement.style.transition = "all 0.2s";

			// Add hover effect
			col.addEventListener("mouseenter", () => {
				iconElement.style.transform = "scale(1.2)";
				iconElement.style.color = "#4361ee";
			});

			col.addEventListener("mouseleave", () => {
				iconElement.style.transform = "scale(1)";
				iconElement.style.color = "";
			});

			col.addEventListener("click", () => {
				this.selectIcon(icon.name);
			});

			col.appendChild(iconElement);
			this.iconGrid.appendChild(col);
		});
	}

	selectIcon(iconName) {
		this.selectedIconPreview.className = iconName;
		this.iconInput.value = iconName;

		if (this.dropdown) {
			this.dropdown.hide();
		}
	}

	setupEventListeners() {
		// Search functionality
		if (this.iconSearch) {
			this.iconSearch.addEventListener("input", () => {
				this.filterIcons();
			});
		}

		// Clear search
		const clearSearchBtn = document.getElementById("clearSearch");
		if (clearSearchBtn) {
			clearSearchBtn.addEventListener("click", () => {
				this.iconSearch.value = "";
				this.filterIcons();
			});
		}

		// Popular icons quick select
		this.setupPopularIcons();
	}

	setupPopularIcons() {
		const popularIcons = [
			"bi-cash-stack",
			"bi-cart",
			"bi-house",
			"bi-car-front",
			"bi-heart-pulse",
			"bi-book",
			"bi-film",
			"bi-wallet"
		];

		const container = document.getElementById("popularIcons");
		if (!container) return;

		popularIcons.forEach(iconName => {
			const col = document.createElement("div");
			col.className = "col text-center";
			col.style.cursor = "pointer";
			col.title = "Klik untuk memilih";

			const iconElement = document.createElement("i");
			iconElement.className = `${iconName} fs-4`;

			col.addEventListener("click", () => {
				this.selectIcon(iconName);
			});

			col.appendChild(iconElement);
			container.appendChild(col);
		});
	}
}
