const fs = require('fs');
const path = require('path');

function walkDir(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = path.join(dir, file);
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) { 
            results = results.concat(walkDir(file));
        } else if (file.endsWith('.vue')) {
            results.push(file);
        }
    });
    return results;
}

const vueFiles = walkDir('./resources/js/vue/views');

for (const file of vueFiles) {
    let content = fs.readFileSync(file, 'utf8');
    let changed = false;

    // Check if file uses DataTable with @query="onQuery"
    if (content.includes('@query="onQuery"')) {
        
        // Ensure server-side="true" is there if missing
        if (!content.includes(':server-side') && !content.includes('server-side')) {
            content = content.replace(/<DataTable([^>]*?)>/, '<DataTable$1 :server-side="true">');
            changed = true;
            console.log(`Added server-side to ${file}`);
        }

        // Fix onQuery mapping if the file has a keyword property in its form/filters
        // Pattern 1: onQuery({ page, pageSize, ... q }) => { ... if (q !== undefined) searchForm.value.keyword = q; }
        if (content.includes('keyword: \'\'') || content.includes('keyword: ""') || content.includes('filters.keyword')) {
            
            // Check if onQuery already maps q to keyword
            if (!content.includes('.keyword = q') && !content.includes('.keyword = e.q')) {
                // Typical onQuery implementations:
                // const onQuery = ({ page, pageSize, sortBy, sortOrder }) => {
                // function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }
                
                // Let's replace the whole onQuery block using regex, or simply manually adjust them.
                console.log(`NEEDS Q MAPPING: ${file}`);
            }
        } else {
            // If it doesn't have a keyword field, maybe it shouldn't be searchable?
            if (!content.includes(':searchable="false"') && !content.includes('searchable="false"')) {
                 console.log(`NO KEYWORD BUT SEARCHABLE: ${file}`);
            }
        }
    }
}
