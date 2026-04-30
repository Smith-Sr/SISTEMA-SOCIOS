async function compartirCarnet(event) {
    const btn           = event.currentTarget;
    const textoOriginal = btn.innerHTML;

    btn.innerHTML = '⏳ Generando imagen...';
    btn.disabled  = true;

    try {
        const canvas = await html2canvas(document.querySelector('.carnet'), {
            scale:           3,
            backgroundColor: '#151829',
            useCORS:         true,
            logging:         false
        });

        // Datos inyectados desde PHP via atributos data-*
        const el     = document.getElementById('carnet-data');
        const nombre = el.dataset.nombre;
        const dni    = el.dataset.dni;
        const estado = el.dataset.estado;
        const texto  = `🎾 *Club Lawn Tennis*\n👤 ${nombre}\n🪪 DNI: ${dni}\n✅ Estado: ${estado}`;

        canvas.toBlob(async (blob) => {
            const archivo = new File([blob], `carnet-${dni}.png`, { type: 'image/png' });

            const puedeCompartir = navigator.share &&
                                   navigator.canShare &&
                                   navigator.canShare({ files: [archivo] });

            if (puedeCompartir) {
                await navigator.share({
                    title: `Carnet - ${nombre}`,
                    text:  texto,
                    files: [archivo]
                });
            } else {
                // Fallback: descargar imagen
                const url = URL.createObjectURL(blob);
                const a   = document.createElement('a');
                a.href     = url;
                a.download = `carnet-${dni}.png`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }

            btn.innerHTML = textoOriginal;
            btn.disabled  = false;

        }, 'image/png');

    } catch (err) {
        if (err.name !== 'AbortError') {
            alert('No se pudo compartir. Intenta de nuevo.');
        }
        btn.innerHTML = textoOriginal;
        btn.disabled  = false;
    }
}