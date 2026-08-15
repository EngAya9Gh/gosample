const fs = require('fs');

const filesToFix = [
    'resources/js/vue/views/Attendances/AttendancesList.vue',
    'resources/js/vue/views/Cars/CarsList.vue',
    'resources/js/vue/views/Containers/ContainersList.vue',
    'resources/js/vue/views/DailyOperation/DailyOperationList.vue',
    'resources/js/vue/views/Drivers/DriversList.vue',
    'resources/js/vue/views/MoneyTransfers/MoneyTransfersList.vue',
    'resources/js/vue/views/Notifications/NotificationsList.vue',
    'resources/js/vue/views/ShiftTemplates/ShiftTemplatesList.vue',
    'resources/js/vue/views/Shipments/ShipmentsList.vue',
    'resources/js/vue/views/Tasks/TasksList.vue',
    'resources/js/vue/views/Zones/ZonesList.vue'
];

for (const file of filesToFix) {
    if (!fs.existsSync(file)) continue;
    let content = fs.readFileSync(file, 'utf8');

    // Skip TasksList if it already has searchable="false"
    if (file.includes('TasksList') && content.includes('searchable="false"')) continue;

    let newContent = content;

    // Pattern A: const onQuery = ({ ... }) => { ... doSearch(...); };
    const patternA = /const onQuery = \(\{(.*?)\}\) => \{([\s\S]*?)doSearch\((.*?)\);/g;
    newContent = newContent.replace(patternA, (match, args, body, doSearchArgs) => {
        if (!args.includes('q')) {
            args = args + ', q';
        }
        if (!body.includes('.keyword = q')) {
            body = body + `  if (q !== undefined) searchForm.value.keyword = q;\n`;
        }
        return `const onQuery = ({${args}}) => {${body}doSearch(${doSearchArgs});`;
    });

    // Pattern B: function onQuery(q) { reload({ page: q.page, pageSize: q.pageSize }); }
    const patternB = /function onQuery\((.*?)\) \{\s*reload\(\{\s*page: (.*?)\.page,\s*pageSize: \2\.pageSize\s*\}\);\s*\}/g;
    newContent = newContent.replace(patternB, (match, arg, argRef) => {
        return `function onQuery(${arg}) { \n  if (${arg}.q !== undefined) filters.keyword = ${arg}.q;\n  reload({ page: ${argRef}.page, pageSize: ${argRef}.pageSize }); \n}`;
    });

    // Handle Cases where `filters.page = q.page;` is used inside a block instead of `reload`
    // (This handles ones like DriversList if not already updated)
    const patternC = /function onQuery\((.*?)\) \{([\s\S]*?)fetch\(\);\s*\}/g;
    newContent = newContent.replace(patternC, (match, arg, body) => {
        if (!body.includes('.keyword =')) {
            return `function onQuery(${arg}) {${body}  if (${arg}.q !== undefined) filters.keyword = ${arg}.q;\n  fetch();\n}`;
        }
        return match;
    });

    if (content !== newContent) {
        fs.writeFileSync(file, newContent, 'utf8');
        console.log(`Fixed ${file}`);
    }
}
