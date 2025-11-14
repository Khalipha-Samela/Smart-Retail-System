// Autocomplete search functionality
class SearchAutocomplete {
    constructor() {
        this.searchInput = document.querySelector('input[name="search"]');
        this.autocompleteList = null;
        this.products = [];
        this.isLoading = false;
        
        if (this.searchInput) {
            this.initAutocomplete();
        }
    }
    
    initAutocomplete() {
        // Create autocomplete dropdown
        this.autocompleteList = document.createElement('div');
        this.autocompleteList.className = 'autocomplete-list';
        this.autocompleteList.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            max-height: 300px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            display: none;
            margin-top: 0.25rem;
        `;
        
        this.searchInput.parentNode.style.position = 'relative';
        this.searchInput.parentNode.appendChild(this.autocompleteList);
        
        // Add event listeners
        this.searchInput.addEventListener('input', this.debounce(this.handleInput.bind(this), 300));
        this.searchInput.addEventListener('focus', this.handleFocus.bind(this));
        this.searchInput.addEventListener('keydown', this.handleKeydown.bind(this));
        
        // Close autocomplete when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.autocompleteList.contains(e.target)) {
                this.hideAutocomplete();
            }
        });
        
        // Load product data
        this.loadProductData();
    }
    
    async loadProductData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        try {
            const response = await fetch('../../api/products-autocomplete.php');
            if (response.ok) {
                this.products = await response.json();
            }
        } catch (error) {
            console.error('Failed to load product data:', error);
        } finally {
            this.isLoading = false;
        }
    }
    
    handleInput(event) {
        const query = event.target.value.toLowerCase().trim();
        
        if (query.length < 2) {
            this.hideAutocomplete();
            return;
        }
        
        const matches = this.filterProducts(query);
        this.showAutocomplete(matches, query);
    }
    
    handleFocus() {
        const query = this.searchInput.value.toLowerCase().trim();
        if (query.length >= 2) {
            const matches = this.filterProducts(query);
            this.showAutocomplete(matches, query);
        }
    }
    
    handleKeydown(event) {
        const items = this.autocompleteList.querySelectorAll('.autocomplete-item');
        const activeItem = this.autocompleteList.querySelector('.autocomplete-item.active');
        let activeIndex = Array.from(items).indexOf(activeItem);
        
        switch(event.key) {
            case 'ArrowDown':
                event.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                this.setActiveItem(items[activeIndex]);
                break;
                
            case 'ArrowUp':
                event.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                this.setActiveItem(items[activeIndex]);
                break;
                
            case 'Enter':
                if (activeItem) {
                    event.preventDefault();
                    this.selectItem(activeItem);
                }
                break;
                
            case 'Escape':
                this.hideAutocomplete();
                break;
        }
    }
    
    filterProducts(query) {
        return this.products.filter(product => 
            product.name.toLowerCase().includes(query) ||
            (product.category && product.category.toLowerCase().includes(query)) ||
            (product.description && product.description.toLowerCase().includes(query))
        ).slice(0, 8); // Limit to 8 results
    }
    
    showAutocomplete(matches, query) {
        if (matches.length === 0) {
            this.hideAutocomplete();
            return;
        }
        
        this.autocompleteList.innerHTML = '';
        
        matches.forEach((product, index) => {
            const item = this.createAutocompleteItem(product, query, index === 0);
            this.autocompleteList.appendChild(item);
        });
        
        this.autocompleteList.style.display = 'block';
    }
    
    createAutocompleteItem(product, query, isFirst = false) {
        const item = document.createElement('div');
        item.className = `autocomplete-item ${isFirst ? 'active' : ''}`;
        item.style.cssText = `
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f8fafc;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.2s;
        `;
        
        item.innerHTML = `
            <div style="flex: 1;">
                <div style="font-weight: 600; margin-bottom: 4px;">
                    ${this.highlightText(product.name, query)}
                </div>
                <div style="font-size: 0.8rem; color: #64748b;">
                    ${product.category ? 'in ' + product.category : ''}
                    ${product.description ? ' • ' + this.truncateText(product.description, 40) : ''}
                </div>
            </div>
            <div style="font-weight: bold; color: #0284c7; white-space: nowrap; margin-left: 12px;">
                R${product.price}
            </div>
        `;
        
        item.addEventListener('click', () => {
            this.selectItem(item, product);
        });
        
        item.addEventListener('mouseenter', () => {
            this.setActiveItem(item);
        });
        
        return item;
    }
    
    selectItem(item, product = null) {
        if (!product) {
            // Extract product info from the item
            const nameElement = item.querySelector('div:first-child div:first-child');
            product = {
                name: nameElement.textContent.replace(/<[^>]*>/g, '') // Remove HTML tags
            };
        }
        
        this.searchInput.value = product.name;
        this.hideAutocomplete();
        
        // Auto-submit the form
        const form = this.searchInput.closest('form');
        if (form) {
            form.submit();
        }
    }
    
    setActiveItem(item) {
        // Remove active class from all items
        this.autocompleteList.querySelectorAll('.autocomplete-item').forEach(i => {
            i.classList.remove('active');
            i.style.backgroundColor = '';
        });
        
        // Add active class to selected item
        item.classList.add('active');
        item.style.backgroundColor = '#f8fafc';
    }
    
    highlightText(text, query) {
        if (!query) return text;
        
        const regex = new RegExp(`(${this.escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<mark style="background-color: #fef3c7; padding: 2px 0;">$1</mark>');
    }
    
    escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    truncateText(text, length) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }
    
    hideAutocomplete() {
        this.autocompleteList.style.display = 'none';
        this.autocompleteList.querySelectorAll('.autocomplete-item').forEach(item => {
            item.classList.remove('active');
        });
    }
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new SearchAutocomplete();
});