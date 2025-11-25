/**
 * Split First Name into First and Remaining Names
 * 
 * Extracts the first word from 'first_name' column into 'firstname_extract'
 * and puts the remaining words into 'secondname_extract'.
 * 
 * Example:
 *   "Diana Carolina Pico Rueda" → 
 *   firstname_extract: "Diana"
 *   secondname_extract: "Carolina Pico Rueda"
 * 
 * Usage:
 *   1. Open Google Sheets with your Trello export
 *   2. Extensions → Apps Script
 *   3. Add this code to a new file or existing script
 *   4. Save and refresh sheet
 *   5. Use "Name Extraction" → "Split First Name"
 */

/**
 * Add menu item for this function
 * (Add this to your existing onOpen function or use standalone)
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('Name Extraction')
    .addItem('Extract Names from Card Name', 'extractNamesFromCardName')
    .addItem('Split First Name', 'splitFirstName')
    .addItem('Preview First Name Split (First 10)', 'previewFirstNameSplit')
    .addToUi();
}

/**
 * Main function: Split first_name into firstname_extract and secondname_extract
 */
function splitFirstName() {
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
  const firstNameCol = findColumnIndex(headers, ['first_name', 'First Name', 'FirstName', 'first name', 'Name']);
  let firstnameExtractCol = findColumnIndex(headers, ['firstname_extract', 'Firstname Extract', 'FirstnameExtract']);
  let secondnameExtractCol = findColumnIndex(headers, ['secondname_extract', 'Secondname Extract', 'SecondnameExtract']);
  
  // Validate required columns exist
  if (firstNameCol === -1) {
    ui.alert('Error', 'Could not find "first_name" column', ui.ButtonSet.OK);
    return;
  }
  
  // Create firstname_extract column if it doesn't exist
  if (firstnameExtractCol === -1) {
    firstnameExtractCol = headers.length;
    sheet.getRange(1, firstnameExtractCol + 1).setValue('firstname_extract');
    Logger.log('Created new column: firstname_extract');
  }
  
  // Create secondname_extract column if it doesn't exist
  if (secondnameExtractCol === -1) {
    secondnameExtractCol = headers.length + (firstnameExtractCol === headers.length ? 1 : 0);
    sheet.getRange(1, secondnameExtractCol + 1).setValue('secondname_extract');
    Logger.log('Created new column: secondname_extract');
  }
  
  // Get surname column index
  const surnameCol = headers.indexOf('surname');
  if (surnameCol === -1) {
    SpreadsheetApp.getUi().alert('Error: Column "surname" not found in the active sheet.');
    return;
  }
  
  // Process each row
  let processedCount = 0;
  let singleNameCount = 0;
  let multipleNameCount = 0;
  let skippedCount = 0;
  
  for (let i = 1; i < data.length; i++) { // Start at 1 to skip header
    const firstNameValue = data[i][firstNameCol];
    const surnameValue = data[i][surnameCol];
    const firstnameExtractValue = data[i][firstnameExtractCol];
    const secondnameExtractValue = data[i][secondnameExtractCol];
    
    // Skip if surname is not empty
    if (surnameValue && typeof surnameValue === 'string' && surnameValue.trim() !== '') {
      skippedCount++;
      continue;
    }
    
    // Skip if both extract columns already have values
    if (firstnameExtractValue && typeof firstnameExtractValue === 'string' && firstnameExtractValue.trim() !== '' &&
        secondnameExtractValue && typeof secondnameExtractValue === 'string' && secondnameExtractValue.trim() !== '') {
      skippedCount++;
      continue;
    }
    
    if (firstNameValue && typeof firstNameValue === 'string' && firstNameValue.trim() !== '') {
      processedCount++;
      
      const result = splitName(firstNameValue);
      
      // Write to columns
      sheet.getRange(i + 1, firstnameExtractCol + 1).setValue(result.firstName);
      
      if (result.restOfName) {
        sheet.getRange(i + 1, secondnameExtractCol + 1).setValue(result.restOfName);
        multipleNameCount++;
        Logger.log(`Row ${i + 1}: "${firstNameValue}" → "${result.firstName}" + "${result.restOfName}"`);
      } else {
        // Clear secondname_extract if there's only one name
        sheet.getRange(i + 1, secondnameExtractCol + 1).setValue('');
        singleNameCount++;
        Logger.log(`Row ${i + 1}: "${firstNameValue}" → "${result.firstName}" (single name)`);
      }
    }
  }
  
  // Show summary
  const message = `Processing complete!\n\n` +
                  `Rows processed: ${processedCount}\n` +
                  `Single names: ${singleNameCount}\n` +
                  `Multiple names split: ${multipleNameCount}\n` +
                  `Rows skipped (already filled): ${skippedCount}\n\n` +
                  `Results written to "firstname_extract" and "secondname_extract" columns.`;
  
  ui.alert('First Name Split Complete', message, ui.ButtonSet.OK);
  Logger.log(message);
}

/**
 * Split a full name into first name and rest of name
 */
function splitName(fullName) {
  if (!fullName || typeof fullName !== 'string') {
    return { firstName: '', restOfName: null };
  }
  
  // Trim and normalize spaces
  const trimmed = fullName.trim().replace(/\s+/g, ' ');
  
  // Split by space
  const parts = trimmed.split(' ');
  
  if (parts.length === 0) {
    return { firstName: '', restOfName: null };
  }
  
  if (parts.length === 1) {
    return { firstName: parts[0], restOfName: null };
  }
  
  // First word is firstName, rest is restOfName
  const firstName = parts[0];
  const restOfName = parts.slice(1).join(' ');
  
  return { firstName, restOfName };
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
 * Preview split (first 10 rows) - for testing
 */
function previewFirstNameSplit() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const ui = SpreadsheetApp.getUi();
  
  const dataRange = sheet.getDataRange();
  const data = dataRange.getValues();
  
  if (data.length === 0) {
    ui.alert('Error', 'Sheet is empty', ui.ButtonSet.OK);
    return;
  }
  
  const headers = data[0];
  const firstNameCol = findColumnIndex(headers, ['first_name', 'First Name', 'FirstName', 'first name', 'Name']);
  
  if (firstNameCol === -1) {
    ui.alert('Error', 'Could not find "first_name" column', ui.ButtonSet.OK);
    return;
  }
  
  let preview = 'Preview of First Name Split (First 10 Rows):\n\n';
  const maxRows = Math.min(11, data.length); // Header + 10 data rows
  
  for (let i = 1; i < maxRows; i++) {
    const firstNameValue = data[i][firstNameCol];
    
    if (firstNameValue && typeof firstNameValue === 'string') {
      const result = splitName(firstNameValue);
      
      preview += `Row ${i + 1}:\n`;
      preview += `  Original: "${firstNameValue}"\n`;
      preview += `  First: "${result.firstName}"\n`;
      if (result.restOfName) {
        preview += `  Rest: "${result.restOfName}"\n`;
      } else {
        preview += `  Rest: (none - single name)\n`;
      }
      preview += '\n';
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
 * Test function - Run this to test name splitting
 * (Can be run from Apps Script editor)
 */
function testNameSplit() {
  const testCases = [
    "Diana Carolina Pico Rueda",
    "John Smith",
    "Maria",
    "José Antonio García López",
    "Anna-Marie",
    "   Multiple   Spaces   Between  ",
  ];
  
  Logger.log('=== Name Split Tests ===\n');
  
  testCases.forEach((testCase, index) => {
    const result = splitName(testCase);
    Logger.log(`Test ${index + 1}:`);
    Logger.log(`  Input: "${testCase}"`);
    Logger.log(`  First: "${result.firstName}"`);
    Logger.log(`  Rest: "${result.restOfName || '(none)'}"`);
    Logger.log('');
  });
}
