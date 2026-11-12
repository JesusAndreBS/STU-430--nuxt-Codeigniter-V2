/**
 * Conexion con la tableta Wacom STU-430 a traves del servicio SigCaptX.
 *
 * El SDK wgssStuSdk.js usa esta API:
 *   new sdk.UsbInterface() + await usb.Constructor() + await usb.connect(dispositivo, true)
 *   new sdk.Protocol()      + await protocol.Constructor(usb)
 *   new sdk.Tablet()        + await tablet.Constructor(usb)
 *   new sdk.ProtocolHelper.ReportHandler() + await handler.startReporting(tablet, false)
 *
 * El Protocol se crea ANTES que el Tablet: el Tablet absorbe la interfaz USB
 * y, si se hace al reves, falla con "UsbInterface instance not found".
 * La tableta se devuelve al servicio cerrando el TABLET (usb.disconnect()
 * despues falla y deja la tableta bloqueada para los demas).
 */
import { comprobar } from './stu'
export async function conectarTableta(STU, devices, { onLapiz, areaEscritura }) {
  const usb = new STU.UsbInterface()
  await usb.Constructor()
  comprobar(await usb.connect(devices[0], true))

  const protocol = new STU.Protocol()
  comprobar(await protocol.Constructor(usb))

  const tablet = new STU.Tablet()
  comprobar(await tablet.Constructor(usb))

  // El area de escritura no la soportan todos los firmwares: si falla, se
  // puede seguir trabajando igual. El modo "tinta" lo activa y desactiva la
  // aplicacion segun el paso (solo se firma en la pantalla de firma).
  if (areaEscritura) {
    try {
      await protocol.setHandwritingDisplayArea(areaEscritura)
    } catch { /* firmware sin area de escritura */ }
  }

  const handler = new STU.ProtocolHelper.ReportHandler()
  handler.onReportPenData = onLapiz
  comprobar(await handler.startReporting(tablet, false))

  return { usb, protocol, tablet, handler }
}

/**
 * Devuelve la tableta al servicio. Los mensajes se lanzan sin esperar uno
 * detras de otro: el navegador alcanza a mandarlos aunque la pagina se este
 * cerrando, que es justo cuando mas hace falta devolver la tableta.
 */
export function soltarTableta({ handler, protocol, tablet, usb }) {
  const enviar = (accion) => {
    try {
      accion()?.catch?.(() => { /* ya suelta */ })
    } catch { /* ya suelta */ }
  }

  enviar(() => tablet?.disconnect())
  enviar(() => protocol?.setInkingMode(0))
  enviar(() => handler?.stopReporting())
  enviar(() => usb?.disconnect())
}
