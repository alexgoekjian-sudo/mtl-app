/**
 * Extract Name from Card Name Column
 * 
 * Extracts student/contact names from various Trello card name formats:
 * 1. "Contact Form: Contact by [Name]"
 * 2. "Contact Form: Free intake from [Name]"
 * 3. "Enroll [Type] Course - [Name]" (e.g., Business English, Intensive)
 * 4. "Quick Message: Contact by [Name]"
 * 
 * Places extracted name into 'name_extract' column.
 * 
 * Usage:
 *   1. Open Google Sheets with your Trello export
 *   2. Extensions → Apps Script
 *   3. Paste this code
 *   4. Save and refresh sheet
 *   5. Use "Name Extraction" → "Extract Names from Card Name"
 */

/**
 * Adds custom menu to Google Sheets
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('Name Extraction')
    .addItem('Extract Names from Card Name', 'extractNamesFromCardName')
    .addItem('Preview Extraction (First 10 Rows)', 'previewExtraction')
    .addToUi();
}

/**
 * Main function: Extract names from card_name column
 */
function extractNamesFromCardName() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const ui = SpreadsheetApp.getUi();
  
  // Get all data
  const dataRange = sheet.getDataRange();
  const data = dataRange.getValues();
  
  if (data.length === 0) {
    ui.alert('Error', 'Sheet is empty', ui.ButtonSet.OK);
    return;
  }
  
  // Find column indices
  const headers = data[0];
  const cardNameCol = findColumnIndex(headers, ['card_name', 'Card Name', 'CardName', 'card name', 'Title', 'title']);
  let nameExtractCol = findColumnIndex(headers, ['name_extract', 'Name Extract', 'NameExtract', 'name extract']);
  
  // Validate required columns exist
  if (cardNameCol === -1) {
    ui.alert('Error', 'Could not find "card_name" column', ui.ButtonSet.OK);
    return;
  }
  
  // Create name_extract column if it doesn't exist
  if (nameExtractCol === -1) {
    nameExtractCol = headers.length;
    sheet.getRange(1, nameExtractCol + 1).setValue('name_extract');
    Logger.log('Created new column: name_extract');
  }
  
  // Process each row
  let processedCount = 0;
  let namesFound = 0;
  let namesNotFound = 0;
  const patternMatches = {
    'Form Name Field': 0,
    'From Email': 0,
    'Contact by': 0,
    'Contact from': 0,
    'Free intake from': 0,
    'Enroll Course': 0,
    'Quick Message': 0,
    'Other': 0
  };
  
  for (let i = 1; i < data.length; i++) { // Start at 1 to skip header
    const cardNameValue = data[i][cardNameCol];
    const nameExtractValue = data[i][nameExtractCol];
    
    // Skip if name_extract already has a value
    if (nameExtractValue && typeof nameExtractValue === 'string' && nameExtractValue.trim() !== '') {
      continue;
    }
    
    if (cardNameValue && typeof cardNameValue === 'string') {
      processedCount++;
      
      // Extract name using pattern matching
      const result = extractNameFromCardName(cardNameValue);
      
      if (result.name) {
        // Write to name_extract column
        sheet.getRange(i + 1, nameExtractCol + 1).setValue(result.name);
        namesFound++;
        patternMatches[result.pattern]++;
        Logger.log(`Row ${i + 1}: Extracted "${result.name}" using pattern "${result.pattern}" from "${cardNameValue}"`);
      } else {
        namesNotFound++;
        Logger.log(`Row ${i + 1}: No name pattern matched in "${cardNameValue}"`);
      }
    }
  }
  
  // Show summary
  const patternSummary = Object.keys(patternMatches)
    .filter(key => patternMatches[key] > 0)
    .map(key => `  ${key}: ${patternMatches[key]}`)
    .join('\n');
  
  const message = `Processing complete!\n\n` +
                  `Total rows processed: ${processedCount}\n` +
                  `Names extracted: ${namesFound}\n` +
                  `No name found: ${namesNotFound}\n\n` +
                  `Pattern breakdown:\n${patternSummary}\n\n` +
                  `Results written to "name_extract" column.`;
  
  ui.alert('Name Extraction Complete', message, ui.ButtonSet.OK);
  Logger.log(message);
}

/**
 * Extract name from card name using multiple pattern matching rules
 * 
 * Patterns:
 * 1. "Contact Form: Contact by [Name]"
 * 2. "Contact Form: Free intake from [Name]"
 * 3. "Enroll [Type] Course - [Name]"
 * 4. "Quick Message: Contact by [Name]"
 * 5. "From: [Name] <email@example.com>"
 * 6. "Name: [Full Name]" (from form submissions)
 */
function extractNameFromCardName(cardName) {
  if (!cardName || typeof cardName !== 'string') {
    return { name: null, pattern: null };
  }
  
  const text = cardName.trim();
  
  // Pattern 6: "Name: [Full Name]" (often appears in form submissions)
  // Look for explicit "Name:" field in the text
  let match = text.match(/\bName:\s*([^\n]+?)(?:\n|$)/i);
  if (match && match[1]) {
    const extractedName = match[1].trim();
    // Make sure it's not empty, not just whitespace, and looks like a real name
    if (extractedName.length > 0 && /[a-zA-Z]{2,}/.test(extractedName)) {
      return {
        name: cleanName(extractedName),
        pattern: 'Form Name Field'
      };
    }
  }
  
  // Pattern 5: "From: [Name] <email@example.com>"
  match = text.match(/From:\s*([^<\n]+)\s*</i);
  if (match && match[1]) {
    return {
      name: cleanName(match[1]),
      pattern: 'From Email'
    };
  }
  
  // Pattern 1 & 4: "Contact by [Name]" or "Quick Message: Contact by [Name]"
  match = text.match(/(?:Contact|Quick Message).*?(?:contact\s+)?by\s+(.+?)$/i);
  if (match && match[1]) {
    return {
      name: cleanName(match[1]),
      pattern: text.toLowerCase().includes('quick message') ? 'Quick Message' : 'Contact by'
    };
  }
  
  // Pattern 2: "Free intake from [Name]" or "Contact from [Name]"
  match = text.match(/(?:Free intake|Contact).*?from\s+(.+?)$/i);
  if (match && match[1]) {
    return {
      name: cleanName(match[1]),
      pattern: text.toLowerCase().includes('free intake') ? 'Free intake from' : 'Contact from'
    };
  }
  
  // Pattern 3: "Enroll [Type] Course - [Name]"
  // Matches: "Enroll Business English Course - Name", "Enroll Intensive Course - Name", etc.
  match = text.match(/Enroll\s+.*?Course\s*-\s*(.+?)$/i);
  if (match && match[1]) {
    return {
      name: cleanName(match[1]),
      pattern: 'Enroll Course'
    };
  }
  
  // Additional pattern: Generic "- [Name]" at end (fallback)
  // Only match if it looks like a name (at least 2 words, starts with capital)
  match = text.match(/[-–—]\s*([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)\s*$/);
  if (match && match[1]) {
    return {
      name: cleanName(match[1]),
      pattern: 'Other'
    };
  }
  
  return { name: null, pattern: null };
}

/**
 * Clean and normalize extracted name
 */
function cleanName(name) {
  if (!name) return null;
  
  // Remove extra whitespace
  name = name.trim();
  
  // Remove trailing punctuation
  name = name.replace(/[.,;:!?]+$/, '');
  
  // Remove common artifacts
  name = name.replace(/\s+/g, ' '); // Normalize spaces
  
  // Capitalize first letter of each word (Title Case)
  name = name.split(' ')
    .map(word => {
      if (word.length === 0) return '';
      return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    })
    .join(' ');
  
  return name;
}

/**
 * Find column index by matching multiple possible header names
 */
function findColumnIndex(headers, possibleNames) {
  for (let i = 0; i < headers.length; i++) {
    const header = headers[i];
    if (header && typeof header === 'string') {
      const normalizedHeader = header.trim().toLowerCase();
      for (const name of possibleNames) {
        if (normalizedHeader === name.toLowerCase()) {
          return i;
        }
      }
    }
  }
  return -1;
}

/**
 * Preview extraction (first 10 rows) - for testing
 */
function previewExtraction() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const ui = SpreadsheetApp.getUi();
  
  const dataRange = sheet.getDataRange();
  const data = dataRange.getValues();
  
  if (data.length === 0) {
    ui.alert('Error', 'Sheet is empty', ui.ButtonSet.OK);
    return;
  }
  
  const headers = data[0];
  const cardNameCol = findColumnIndex(headers, ['card_name', 'Card Name', 'CardName', 'card name', 'Title']);
  
  if (cardNameCol === -1) {
    ui.alert('Error', 'Could not find "card_name" column', ui.ButtonSet.OK);
    return;
  }
  
  let preview = 'Preview of Name Extraction (First 10 Rows):\n\n';
  const maxRows = Math.min(11, data.length); // Header + 10 data rows
  
  for (let i = 1; i < maxRows; i++) {
    const cardNameValue = data[i][cardNameCol];
    
    if (cardNameValue && typeof cardNameValue === 'string') {
      const result = extractNameFromCardName(cardNameValue);
      
      if (result.name) {
        preview += `Row ${i + 1}: "${result.name}"\n`;
        preview += `  Pattern: ${result.pattern}\n`;
        preview += `  Original: ${cardNameValue}\n\n`;
      } else {
        preview += `Row ${i + 1}: [NO MATCH]\n`;
        preview += `  Original: ${cardNameValue}\n\n`;
      }
    }
  }
  
  Logger.log(preview);
  
  // Show in alert (truncate if too long)
  if (preview.length > 1000) {
    preview = preview.substring(0, 997) + '...';
  }
  
  ui.alert('Preview', preview, ui.ButtonSet.OK);
}

/**
 * Test function - Run this to test pattern matching
 * (Can be run from Apps Script editor)
 */
function testPatterns() {
  const testCases = [
    "From: Natalia Amaya Serpa <natalia50993@gmail.com>\nSubject: Open day\n\nName: Natalia Amaya Serpa\nEmail : natalia50993@gmail.com",
    "From: Yong <huangyong_yn@qq.com>",
    "Contact Form: Contact by Berfin Demirbilek",
    "Contact Form: Free intake from adel",
    "Enroll Business English Course - Collins Muhadia Bisia",
    "Enroll Intensive Course - John Smith",
    "Quick Message: Contact by Hind",
    "Enroll A2 Elementary Course - Maria González",
    "Contact Form: Contact by Sarah Johnson",
    "Free intake from Mohammed Ali",
    "From: John Smith <john.smith@example.com>",
  ];
  
  Logger.log('=== Pattern Matching Tests ===\n');
  
  testCases.forEach((testCase, index) => {
    const result = extractNameFromCardName(testCase);
    Logger.log(`Test ${index + 1}:`);
    Logger.log(`  Input: "${testCase}"`);
    Logger.log(`  Extracted: "${result.name}"`);
    Logger.log(`  Pattern: ${result.pattern}`);
    Logger.log('');
  });
}
