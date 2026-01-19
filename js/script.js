// Inicializa app inmediatamente
const app = new App();
window.app = app;

function App() {
    // Método para procesar el clic en los botones
    this.processingButton = function(event) {
        console.log('Botón clicado:', event.currentTarget.dataset.button);  // Debug

        const button = event.currentTarget;
        const track = button.parentNode.querySelector('#track');
        const carruselList = button.parentNode;
        const carrusel = track.querySelectorAll('.carrusel');

        const carruselWidth = carrusel[0].offsetWidth;
        const trackWidth = track.offsetWidth;
        const listWidth = carruselList.offsetWidth;

        // Obtiene la posición actual
        const transformValue = track.style.transform || 'translateX(0px)';
        const match = transformValue.match(/translateX\(([^)]+)px\)/);
        let currentPosition = match ? parseFloat(match[1]) : 0;

        console.log('Posición actual:', currentPosition, 'TrackWidth:', trackWidth, 'ListWidth:', listWidth);

        if (button.dataset.button === "button-prev") {
            if (currentPosition < 0) {
                currentPosition += carruselWidth;
                track.style.transform = `translateX(${currentPosition}px)`;
                console.log('Moviendo a prev:', currentPosition);
            }
        } else if (button.dataset.button === "button-next") {
            // Corrige la condición: permite mover si no ha llegado al final
            if (Math.abs(currentPosition) < trackWidth - listWidth) {
                currentPosition -= carruselWidth;
                track.style.transform = `translateX(${currentPosition}px)`;
                console.log('Moviendo a next:', currentPosition);
            } else {
                console.log('No se puede mover más a next');
            }
        }
    };
}
