document.addEventListener('DOMContentLoaded', () => {
    const navToggle = document.querySelector('[data-nav-toggle]');
    const mainNav = document.querySelector('[data-main-nav]');
    if (navToggle && mainNav) {
        navToggle.addEventListener('click', () => {
            const expanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!expanded));
            mainNav.classList.toggle('is-open', !expanded);
        });
    }

    document.querySelectorAll('[data-dismiss-flash]').forEach((button) => {
        button.addEventListener('click', () => button.closest('[data-flash]')?.remove());
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach((select) => {
        select.addEventListener('change', () => select.form?.requestSubmit());
    });

    document.querySelectorAll('[data-product-image]').forEach((image) => {
        image.addEventListener('error', () => {
            image.hidden = true;
            image.closest('.product-visual, .featured-product-image')?.classList.add('image-unavailable');
        }, { once: true });
    });

    const roleSelect = document.querySelector('select[name="role"][data-role-select]');
    const roleHint = document.querySelector('[data-role-hint]');
    const roleDescriptions = {
        petani: 'Kelola lahan, jelajahi Niaga Tani, dan pantau harga pasar.',
        penjual: 'Jual produk pertanian dan pantau informasi harga pasar.',
        petani_penjual: 'Kelola lahan, jual produk, dan pantau harga pasar.',
        warga: 'Jelajahi Niaga Tani dan pantau harga pasar.',
    };
    if (roleSelect && roleHint) {
        const updateRoleHint = () => {
            roleHint.textContent = roleDescriptions[roleSelect.value] || '';
        };
        roleSelect.addEventListener('change', updateRoleHint);
        updateRoleHint();
    }

    const commodityData = document.querySelector('#commodity-data');
    const chart = document.querySelector('[data-trader-chart]');
    if (commodityData && chart) {
        const commodities = JSON.parse(commodityData.textContent);
        const grid = chart.querySelector('[data-chart-grid]');
        const labels = chart.querySelector('[data-chart-labels]');
        const line = chart.querySelector('[data-chart-line]');
        const area = chart.querySelector('[data-chart-area]');
        const currentPoint = chart.querySelector('[data-chart-point]');
        const crosshair = chart.querySelector('[data-chart-crosshair]');
        const hoverPoint = chart.querySelector('[data-chart-hover-point]');
        const tooltip = document.querySelector('[data-chart-tooltip]');
        const quoteSymbol = document.querySelector('[data-quote-symbol]');
        const quoteName = document.querySelector('[data-quote-name]');
        const quoteUnit = document.querySelector('[data-quote-unit]');
        const quotePrice = document.querySelector('[data-quote-price]');
        const quoteChange = document.querySelector('[data-quote-change]');
        const chartPeriod = document.querySelector('[data-chart-period]');
        const watchItems = Array.from(document.querySelectorAll('[data-watch-item]'));
        const rangeButtons = Array.from(document.querySelectorAll('[data-chart-range]'));
        let selectedIndex = 0;
        let selectedRange = '1H';
        let chartPoints = [];

        const formatPrice = (value) => `Rp${Math.round(value).toLocaleString('id-ID')}`;
        const svgElement = (name, attributes, text = '') => {
            const element = document.createElementNS('http://www.w3.org/2000/svg', name);
            Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, value));
            if (text) element.textContent = text;
            return element;
        };
        const generateSeries = (commodity) => {
            const pointCount = selectedRange === '1H' ? 36 : 52;
            const end = commodity.price;
            const start = end / (1 + commodity.change / 100);
            const amplitude = end * (selectedRange === '1H' ? 0.0026 : 0.008);
            const values = [];
            for (let index = 0; index < pointCount; index += 1) {
                const progress = index / (pointCount - 1);
                const trend = start + (end - start) * progress;
                const wave = Math.sin((index + commodity.history_seed) * 1.71) * 0.52
                    + Math.cos((index + commodity.history_seed) * 0.49) * 0.31
                    + Math.sin((index + commodity.history_seed) * 2.93) * 0.17;
                values.push(trend + amplitude * wave * Math.sin(Math.PI * progress));
            }
            values[values.length - 1] = end;
            return values;
        };
        const drawChart = () => {
            const commodity = commodities[selectedIndex];
            chartPoints = generateSeries(commodity);
            const xStart = 72;
            const xEnd = 976;
            const yTop = 34;
            const yBottom = 330;
            const minValue = Math.min(...chartPoints);
            const maxValue = Math.max(...chartPoints);
            const padding = Math.max((maxValue - minValue) * 0.2, commodity.price * 0.0007);
            const lower = minValue - padding;
            const upper = maxValue + padding;
            const positions = chartPoints.map((value, index) => ({
                x: xStart + ((xEnd - xStart) * index) / (chartPoints.length - 1),
                y: yBottom - ((value - lower) / (upper - lower)) * (yBottom - yTop),
                value,
            }));

            grid.replaceChildren();
            labels.replaceChildren();
            for (let index = 0; index <= 4; index += 1) {
                const y = yTop + ((yBottom - yTop) * index) / 4;
                const value = upper - ((upper - lower) * index) / 4;
                grid.append(
                    svgElement('line', { x1: xStart, y1: y, x2: xEnd, y2: y, class: 'chart-grid-line' }),
                    svgElement('text', { x: 0, y: y + 4, class: 'chart-axis-label' }, formatPrice(value)),
                );
            }
            const timeLabels = selectedRange === '1H'
                ? ['60 mnt', '45 mnt', '30 mnt', '15 mnt', 'Kini']
                : ['Awal', '25%', '50%', '75%', 'Kini'];
            timeLabels.forEach((label, index) => {
                const x = xStart + ((xEnd - xStart) * index) / 4;
                labels.append(svgElement('text', {
                    x,
                    y: 367,
                    class: 'chart-time-label',
                    'text-anchor': index === 0 ? 'start' : index === 4 ? 'end' : 'middle',
                }, label));
            });
            const path = positions.map((point, index) => `${index ? 'L' : 'M'} ${point.x} ${point.y}`).join(' ');
            line.setAttribute('d', path);
            area.setAttribute('d', `${path} L ${xEnd} ${yBottom} L ${xStart} ${yBottom} Z`);
            currentPoint.setAttribute('cx', positions.at(-1).x);
            currentPoint.setAttribute('cy', positions.at(-1).y);
            chartPoints = positions;

            quoteSymbol.textContent = `${commodity.symbol} · HASIL PANEN`;
            quoteName.textContent = commodity.name;
            quoteUnit.textContent = `Harga ilustrasi · ${commodity.unit}`;
            quotePrice.textContent = formatPrice(commodity.price);
            quoteChange.innerHTML = `${commodity.change >= 0 ? '+' : ''}${commodity.change.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}% <small>ilustrasi</small>`;
            quoteChange.classList.toggle('quote-up', commodity.change >= 0);
            quoteChange.classList.toggle('quote-down', commodity.change < 0);
            chartPeriod.textContent = selectedRange === '1H' ? 'Intrahari · data ilustrasi' : 'Rentang historis · data ilustrasi';
            watchItems.forEach((item) => item.classList.toggle('is-selected', Number(item.dataset.index) === selectedIndex));
            crosshair.setAttribute('visibility', 'hidden');
            hoverPoint.setAttribute('visibility', 'hidden');
            tooltip.hidden = true;
        };

        watchItems.forEach((item) => {
            item.addEventListener('click', () => {
                selectedIndex = Number(item.dataset.index);
                drawChart();
            });
        });
        rangeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                selectedRange = button.dataset.chartRange;
                rangeButtons.forEach((rangeButton) => rangeButton.classList.toggle('is-selected', rangeButton === button));
                drawChart();
            });
        });
        chart.addEventListener('pointermove', (event) => {
            if (!chartPoints.length) return;
            const point = chart.createSVGPoint();
            point.x = event.clientX;
            point.y = event.clientY;
            const chartPoint = point.matrixTransform(chart.getScreenCTM().inverse());
            const index = Math.max(0, Math.min(chartPoints.length - 1, Math.round(((chartPoint.x - 72) / (976 - 72)) * (chartPoints.length - 1))));
            const selectedPoint = chartPoints[index];
            crosshair.setAttribute('x1', selectedPoint.x);
            crosshair.setAttribute('x2', selectedPoint.x);
            crosshair.setAttribute('visibility', 'visible');
            hoverPoint.setAttribute('cx', selectedPoint.x);
            hoverPoint.setAttribute('cy', selectedPoint.y);
            hoverPoint.setAttribute('visibility', 'visible');
            tooltip.textContent = `${formatPrice(selectedPoint.value)} · simulasi`;
            tooltip.hidden = false;
            const bounds = chart.getBoundingClientRect();
            tooltip.style.left = `${Math.max(4, Math.min(bounds.width - tooltip.offsetWidth - 4, ((selectedPoint.x - 72) / (976 - 72)) * bounds.width + 10))}px`;
            tooltip.style.top = `${Math.max(4, ((selectedPoint.y - 34) / (330 - 34)) * bounds.height - 25)}px`;
        });
        chart.addEventListener('pointerleave', () => {
            crosshair.setAttribute('visibility', 'hidden');
            hoverPoint.setAttribute('visibility', 'hidden');
            tooltip.hidden = true;
        });
        drawChart();
    }
});
