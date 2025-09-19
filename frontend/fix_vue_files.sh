
files=(
  "pages/profile.vue:152"
  "pages/planning.vue:417"
  "pages/users/[id].vue:150"
  "pages/users/index.vue:205"
  "pages/interventions/[id]/edit.vue:172"
  "pages/interventions/[id].vue:165"
  "pages/interventions/index.vue:221"
  "pages/interventions/create.vue:33"
  "pages/notifications/history.vue:249"
  "pages/interventions/electrique/[id].vue:135"
)

for file_info in "${files[@]}"; do
  file=$(echo $file_info | cut -d: -f1)
  line=$(echo $file_info | cut -d: -f2)
  echo "Fixing $file at line $line"
  
  # Add missing opening div after <template><div>
  sed -i "2a\    <div>" "$file"
  
done

