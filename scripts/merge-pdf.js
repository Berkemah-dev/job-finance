import fs from 'fs';
import { PDFDocument } from 'pdf-lib';

async function main() {
    const args = process.argv.slice(2);
    if (args.length < 2) {
        console.error('Usage: node merge-pdf.js <outputPath> <inputPath1> [inputPath2 ...]');
        process.exit(1);
    }

    const outputPath = args[0];
    const inputPaths = args.slice(1);

    const mergedPdf = await PDFDocument.create();
    let totalPages = 0;

    for (const filePath of inputPaths) {
        if (!fs.existsSync(filePath)) {
            continue;
        }

        try {
            const fileBytes = fs.readFileSync(filePath);
            const pdfDoc = await PDFDocument.load(fileBytes, { ignoreEncryption: true });
            const pageIndices = pdfDoc.getPageIndices();
            const copiedPages = await mergedPdf.copyPages(pdfDoc, pageIndices);
            for (const page of copiedPages) {
                mergedPdf.addPage(page);
                totalPages++;
            }
        } catch (err) {
            console.error(`Warning: Failed to load ${filePath}: ${err.message}`);
        }
    }

    if (totalPages === 0) {
        console.error('Error: No pages could be imported.');
        process.exit(2);
    }

    const mergedBytes = await mergedPdf.save();
    fs.writeFileSync(outputPath, mergedBytes);
}

main().catch((err) => {
    console.error('Fatal:', err);
    process.exit(1);
});
