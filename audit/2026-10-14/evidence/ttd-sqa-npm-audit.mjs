import fs from 'node:fs';
const root='/Users/fikrifahruroji/Documents/gravity project/ttd-digital';
const lock=JSON.parse(fs.readFileSync(root+'/package-lock.json','utf8'));
const versions={};
for(const [path,pkg] of Object.entries(lock.packages??{})) {
  if (!path || !pkg.version) continue;
  const name=pkg.name??path.split('node_modules/').at(-1);
  if (!versions[name]) versions[name]=[];
  if (!versions[name].includes(pkg.version)) versions[name].push(pkg.version);
}
const res=await fetch('https://registry.npmjs.org/-/npm/v1/security/advisories/bulk',{
  method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(versions),
  signal:AbortSignal.timeout(20000),
});
if(!res.ok) throw new Error('Registry response '+res.status);
const data=await res.json();
fs.writeFileSync('/tmp/ttd-sqa-npm-advisories.json',JSON.stringify({method:'npm registry bulk advisory query using locked package versions; not full npm audit reachability',versions,advisories:data},null,2));
console.log('Packages returned:',Object.keys(data).length);
for(const [name,entries] of Object.entries(data)) {
  console.log(name,'locked='+versions[name].join(','));
  for(const a of entries) console.log(a.severity,a.title,a.vulnerable_versions,a.url);
}
