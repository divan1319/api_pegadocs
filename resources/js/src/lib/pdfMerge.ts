import { PDFDocument } from 'pdf-lib';
import type { Submission } from '@/types/pegadocs';

/**
 * URL del PDF a fusionar: archivo PDF original o PDF generado desde imagen.
 */
export function submissionPdfUrl(s: Submission): string | null {
    if (s.fileType === 'pdf') {
        return s.fileUrl;
    }

    return s.convertedPdfUrl;
}

/**
 * Une en orden los PDFs de las entregas (misma cookie de sesión para descargar desde /storage).
 */
export async function mergeSubmissionPdfs(submissionsOrdered: Submission[]): Promise<Uint8Array> {
    const merged = await PDFDocument.create();

    for (const s of submissionsOrdered) {
        const url = submissionPdfUrl(s);
        if (url === null) {
            continue;
        }

        const res = await fetch(url, { credentials: 'include' });
        if (!res.ok) {
            throw new Error(`No se pudo descargar: ${s.fileName}`);
        }

        const buf = await res.arrayBuffer();
        const doc = await PDFDocument.load(buf);
        const pages = await merged.copyPages(doc, doc.getPageIndices());
        pages.forEach((p) => merged.addPage(p));
    }

    if (merged.getPageCount() === 0) {
        throw new Error(
            'No hay PDFs para unir. Se necesitan entregas en PDF o imágenes con conversión a PDF en el servidor.',
        );
    }

    return merged.save();
}
