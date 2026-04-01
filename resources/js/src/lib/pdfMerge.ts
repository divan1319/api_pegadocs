import { PDFDocument } from 'pdf-lib';
import { http } from '@/api/http';
import type { Submission } from '@/types/pegadocs';

/**
 * Entrega aceptada que se puede incluir en el merge (PDF nativo o imagen embebida en cliente).
 */
export function submissionIsMergeable(s: Submission): boolean {
    if (s.status !== 'accepted') {
        return false;
    }

    return s.fileType === 'pdf' || s.fileType === 'image';
}

function isLikelyPdf(buf: ArrayBuffer): boolean {
    if (buf.byteLength < 5) {
        return false;
    }

    const head = new TextDecoder('ascii').decode(buf.slice(0, 5));

    return head.startsWith('%PDF-');
}

function isPng(buf: ArrayBuffer): boolean {
    if (buf.byteLength < 8) {
        return false;
    }

    const u = new Uint8Array(buf.slice(0, 8));

    return (
        u[0] === 0x89 &&
        u[1] === 0x50 &&
        u[2] === 0x4e &&
        u[3] === 0x47 &&
        u[4] === 0x0d &&
        u[5] === 0x0a &&
        u[6] === 0x1a &&
        u[7] === 0x0a
    );
}

function isJpeg(buf: ArrayBuffer): boolean {
    if (buf.byteLength < 3) {
        return false;
    }

    const u = new Uint8Array(buf.slice(0, 3));

    return u[0] === 0xff && u[1] === 0xd8 && u[2] === 0xff;
}

async function fetchSubmissionFile(submissionId: number, variant: 'original' | 'converted'): Promise<ArrayBuffer> {
    const q = variant === 'converted' ? '?variant=converted' : '';
    const { data } = await http.get<ArrayBuffer>(`/api/v1/submissions/${submissionId}/file${q}`, {
        responseType: 'arraybuffer',
        headers: {
            Accept: 'application/pdf, image/png, image/jpeg, image/webp, */*',
        },
    });

    return data;
}

async function appendPdfBytes(merged: PDFDocument, bytes: ArrayBuffer): Promise<void> {
    if (!isLikelyPdf(bytes)) {
        throw new Error(
            'El archivo no es un PDF válido (¿enlace antiguo a /storage o sesión cerrada?). Recarga la página.',
        );
    }

    const doc = await PDFDocument.load(bytes);
    const pages = await merged.copyPages(doc, doc.getPageIndices());
    pages.forEach((p) => merged.addPage(p));
}

async function appendImageBytes(merged: PDFDocument, bytes: ArrayBuffer, fileName: string): Promise<void> {
    if (isPng(bytes)) {
        const img = await merged.embedPng(bytes);
        const page = merged.addPage([img.width, img.height]);
        page.drawImage(img, { x: 0, y: 0, width: img.width, height: img.height });

        return;
    }

    if (isJpeg(bytes)) {
        const img = await merged.embedJpg(bytes);
        const page = merged.addPage([img.width, img.height]);
        page.drawImage(img, { x: 0, y: 0, width: img.width, height: img.height });

        return;
    }

    throw new Error(
        `Imagen no soportada para unir: ${fileName}. Usa PNG o JPEG, o instala Imagick en el servidor para conversión a PDF.`,
    );
}

/**
 * Descarga vía API (cookies + CORS) y une PDFs; las imágenes se convierten a página con pdf-lib.
 */
export async function mergeSubmissionPdfs(submissionsOrdered: Submission[]): Promise<Uint8Array> {
    const merged = await PDFDocument.create();

    for (const s of submissionsOrdered) {
        if (s.fileType === 'pdf') {
            const bytes = await fetchSubmissionFile(s.id, 'original');
            await appendPdfBytes(merged, bytes);

            continue;
        }

        if (s.fileType === 'image') {
            if (s.convertedPdfUrl) {
                const bytes = await fetchSubmissionFile(s.id, 'converted');
                await appendPdfBytes(merged, bytes);

                continue;
            }

            const bytes = await fetchSubmissionFile(s.id, 'original');
            await appendImageBytes(merged, bytes, s.fileName);

            continue;
        }
    }

    if (merged.getPageCount() === 0) {
        throw new Error('No se pudo generar ninguna página. Revisa que las entregas sean PDF o imagen PNG/JPEG.');
    }

    return merged.save();
}
