const puppeteer = require('puppeteer');
const path = require('path');

async function generatePreview(browser, htmlFile, outputFile) {
    const page = await browser.newPage();
    await page.setViewport({ width: 800, height: 450 });
    
    const htmlPath = path.join(__dirname, htmlFile);
    await page.goto(`file://${htmlPath}`);
    
    // Wait for any animations to complete
    await page.waitForTimeout(1000);
    
    await page.screenshot({
        path: path.join(__dirname, outputFile),
        type: 'png',
        quality: 100
    });
    
    await page.close();
}

async function main() {
    const browser = await puppeteer.launch();
    
    try {
        // Generate preview for template 1
        await generatePreview(browser, 'template-1-preview.html', 'template-1-preview.png');
        console.log('Generated template 1 preview');
        
        // Generate preview for template 2
        await generatePreview(browser, 'template-2-preview.html', 'template-2-preview.png');
        console.log('Generated template 2 preview');
        
        // Generate preview for no template
        await generatePreview(browser, 'template-none-preview.html', 'template-none-preview.png');
        console.log('Generated no template preview');
        
    } catch (error) {
        console.error('Error generating previews:', error);
    } finally {
        await browser.close();
    }
}

main(); 